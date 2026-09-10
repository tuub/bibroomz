#!/bin/bash
# Run as root on a source host to provision a locked-down SSH user and a read-only MariaDB user for
# the import-database CI job (see scripts/db-import.sh). The SSH key can do exactly one thing: run
# scripts/db-dump.sh in the given repo checkout. Nothing else - no shell, no other commands, no
# port/agent/X11 forwarding, no PTY. The dump itself runs as a dedicated read-only MariaDB user,
# kept separate from the app's own full-privilege .env credentials.
#
# Assumes the local MariaDB root user authenticates via unix_socket (the Debian/Ubuntu default), so
# this script - already required to run as root - can reach it with a plain `mariadb` invocation.
#
# Usage: setup-db-dump-user.sh <path-to-public-key> [git-dir] [username]

set -e

public_key_file="$1"
git_dir="${2:-/srv/git/roomz}"
user="${3:-roomz-db-dump}"

if [ -z "$public_key_file" ]; then
	echo "Usage: $0 <path-to-public-key> [git-dir] [username]" >&2
	echo "Example: $0 import-db-key.pub /srv/git/roomz roomz-db-dump" >&2
	exit 1
fi

if [ "$(id -u)" -ne 0 ]; then
	echo "This script must be run as root." >&2
	exit 1
fi

if [ ! -r "$public_key_file" ]; then
	echo "Cannot read public key file: $public_key_file" >&2
	exit 1
fi

if [ ! -f "$git_dir/.env" ]; then
	echo "Cannot read $git_dir/.env - deploy the app there first so its DB_HOST/DB_DATABASE are known." >&2
	exit 1
fi

if ! id "$user" >/dev/null 2>&1; then
	useradd --system --create-home --shell /bin/bash "$user"
fi

# Locked to running exactly scripts/db-dump.sh; the client's requested command is ignored.
# `restrict` (OpenSSH >= 7.2) additionally disables port/agent/X11 forwarding and PTY allocation.
# A real shell is required here (not nologin/false): sshd invokes the forced command via the
# account's shell with -c, and nologin ignores that and just refuses, silently breaking this setup.
public_key="$(cat "$public_key_file")"
home_dir="$(getent passwd "$user" | cut -d: -f6)"
ssh_dir="$home_dir/.ssh"
authorized_keys="$ssh_dir/authorized_keys"

mkdir --parents "$ssh_dir"
echo "restrict,command=\"cd $git_dir && scripts/db-dump.sh\" $public_key" > "$authorized_keys"
chown -R "$user:$user" "$ssh_dir"
chmod 700 "$ssh_dir"
chmod 600 "$authorized_keys"

passwd -l "$user" >/dev/null

# Read-only MariaDB user for the dump, separate from the app's own full-privilege DB_USERNAME. Only
# needs SELECT: the app has no views or triggers, and --single-transaction in scripts/db-dump.sh
# already gives a consistent snapshot of the InnoDB tables without LOCK TABLES.
set -a
source "$git_dir/.env"
set +a

db_password="$(openssl rand -hex 32)"

mariadb <<-SQL
	CREATE OR REPLACE USER '$user'@'$DB_HOST' IDENTIFIED BY '$db_password';
	GRANT SELECT ON \`$DB_DATABASE\`.* TO '$user'@'$DB_HOST';
	FLUSH PRIVILEGES;
SQL

db_credentials="$home_dir/.env.db-dump"
cat > "$db_credentials" <<-EOF
	DB_HOST=$DB_HOST
	DB_DATABASE=$DB_DATABASE
	DB_USERNAME=$user
	DB_PASSWORD=$db_password
EOF
chown "$user:$user" "$db_credentials"
chmod 600 "$db_credentials"

cat <<EOF

Done. "$user" can only run scripts/db-dump.sh in $git_dir over SSH with the matching private key.
That dump connects to MariaDB as the read-only user '$user'@'$DB_HOST' (credentials written to
$db_credentials, readable only by $user).

Set these GitLab CI/CD variables (masked, protected) for the import-database job:
  SOURCE_SSH_USER=$user
  SOURCE_SSH_GIT_DIR=$git_dir
  SOURCE_SSH_HOSTNAME=<this host's address>
  SOURCE_SSH_PRIVATE_KEY=<the private key matching $public_key_file>
  SOURCE_SSH_KNOWN_HOSTS=<output of: ssh-keyscan -H <this host's address>>
EOF
