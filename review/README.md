# Review Apps

Review apps let several branches run side by side on the staging host, each under its own path:

```
https://roomz.onit-ub.tu-berlin.de/review/<branch-slug>
```

Each one is a separate checkout with its own database, Redis key namespace, Reverb listener and systemd services, so
branches cannot interfere with each other. `deploy-review` (manual, on every merge request pipeline) creates or updates
one; `stop-review` removes it. Both jobs are optional and do not block pipeline completion. GitLab also stops a review
app automatically after 14 days of inactivity.

Deployments and teardown run entirely as the deployment user, using `systemctl --user`. The account needs no sudo
privileges. An administrator performs the initial host setup and web server configuration once.

This directory contains the deployment and test scripts, environment template, [GitLab jobs](gitlab-ci.yml),
[nginx](nginx/) and [Apache](apache/) snippets, and [systemd user services](systemd/). Run the repository commands
below from the repository root.

## Host layout

Everything lives under `/srv/review`:

| Path               | Contents                                                      |
| ------------------ | ------------------------------------------------------------- |
| `apps/<slug>/`      | The branch checkout, deployed exactly like staging.            |
| `www/review/<slug>` | Symlink to `apps/<slug>/public`, the only web-exposed path.      |
| `ports/<slug>`      | The Reverb port assigned to the app.                           |
| `cache/`           | Composer and npm caches, shared by every review app.            |
| `review.env`        | Host configuration and secrets, read by `review/review-app.sh`. |

The web root is a directory of symlinks whose layout matches the URL, so one static web server configuration serves
every branch. Each frontend uses `/review/<slug>/reverb/<port>/app/...` for websockets, and the web server forwards only
to loopback ports 6100-6199. Deploying a branch never edits or reloads that configuration.

A review app occupies roughly 90 MB: review deployments install production dependencies only and delete `node_modules`
once the assets are built, which cuts about 780 MB per app. Nothing reads `node_modules` at runtime because the app
builds no SSR bundle, and the shared caches keep the reinstall on the next deployment cheap.

## One-time host setup

1. In an administrator shell, install the host's `acl` package, create the directories, and enable the deployment
   user's systemd manager at boot and after logout:

   ```bash
   apt-get install acl
   mkdir --parents /srv/review/{apps,www/review,ports}
   chown --recursive deploy:deploy /srv/review
   adduser deploy www-data
   loginctl enable-linger deploy
   ```

   The filesystem must support POSIX ACLs. The deployment script sets access and default ACLs on Laravel's writable
   directories before installing dependencies. Files created by either `deploy` (including the user services) or
   `www-data` (PHP-FPM) then remain writable by both. Redeployments only adjust permissions on files owned by `deploy`.
   Start a fresh SSH session as `deploy` after changing its group membership.

2. Grant the shared review database account rights over `review_*` databases only. It creates and drops its own
   databases, so no administrative credentials are needed at deploy time:

   ```sql
   CREATE USER 'roomz_review'@'localhost' IDENTIFIED BY '<password>';
   GRANT ALL PRIVILEGES ON `review\_%`.* TO 'roomz_review'@'localhost';
   ```

3. Write `/srv/review/review.env` (mode `0600`, owned by the deployment user):

   ```bash
   REVIEW_BASE_URL=https://roomz.onit-ub.tu-berlin.de
   REVIEW_DB_USERNAME=roomz_review
   REVIEW_DB_PASSWORD=<password>
   REVIEW_REDIS_PASSWORD=<password>
   REVIEW_AUTH_METHOD=alma
   REVIEW_AUTH_API_ENDPOINT=<endpoint>
   ```

   `REVIEW_AUTH_API_ENDPOINT` has no default because the ALMA provider posts the login name and the plaintext password
   to it: point it at an endpoint you trust, never at a public request echo service such as the `httpbin.org` address
   used for local development.

   Reserve ports `6100`-`6199` exclusively for review Reverb listeners. `REVIEW_PORT_MIN`/`REVIEW_PORT_MAX` select a
   subset of that range (default: the entire range), which must not overlap staging's own Reverb port. The range also
   caps how many review apps can exist at once, so lower it to what the host's disk supports at roughly 90 MB per app.
   `REVIEW_TEST_ACCOUNTS` (default `false`) enables the built-in
   accounts, which have well-known passwords. Passwords are written into the generated `.env` as double-quoted values,
   so they must not contain `"` or `\`.

   Review apps log to the `daily` channel rather than one unbounded `laravel.log`. That channel keeps 14 days, the same
   period GitLab waits before stopping an idle app, so a review app's logs are never pruned during its lifetime.

   Review apps are generated with `APP_DEBUG=false` and their own `APP_KEY` and Reverb secret, so a branch cannot reach
   another app's broadcasts and an error page cannot leak the configuration. They still run unreviewed branch code on a
   public hostname, and every app shares the one `roomz_review` database account, whose grant covers all `review_*`
   databases. Restrict `/review/` to trusted networks in the reverse proxy on a publicly reachable host.

4. As the deployment user, install the templated user services. They run as that user, take the slug as the instance
   name, and read the host and port from the app's own `.env`:

   ```bash
   mkdir --parents ~/.config/systemd/user
   cp review/systemd/roomz-*@.service ~/.config/systemd/user/
   systemctl --user daemon-reload
   ```

5. As an administrator, install the snippets for the host's web server. Both restrict websockets to `/app/` requests
   on ports `6100`-`6199`; neither exposes arbitrary loopback ports or Reverb's API. Adjust the PHP-FPM socket if the
   host uses a different PHP version.

   **nginx** — copy the two snippets:

   ```bash
   mkdir --parents /etc/nginx/roomz-review
   cp review/nginx/*.conf /etc/nginx/roomz-review/
   ```

   Enable the two optional include lines in [`deployment/nginx.conf`](../deployment/nginx.conf):
   `include /etc/nginx/roomz-review/http.conf;` belongs in the `http` context alongside the shared websocket maps,
   and `include /etc/nginx/roomz-review/server.conf;` belongs inside the staging `server` block. Then validate and
   reload:

   ```bash
   nginx -t
   systemctl reload nginx
   ```

   **Apache** — copy the single snippet and enable the modules it needs:

   ```bash
   mkdir --parents /etc/apache2/roomz-review
   cp review/apache/review.conf /etc/apache2/roomz-review/
   a2enmod alias rewrite proxy proxy_http proxy_wstunnel proxy_fcgi
   ```

   Enable the optional `Include /etc/apache2/roomz-review/review.conf` line in
   [`deployment/apache.conf`](../deployment/apache.conf). It belongs inside the staging `VirtualHost` and **before**
   that file's own websocket `RewriteRule`, which otherwise sends review websockets to staging's Reverb listener; the
   `RewriteCond %{REQUEST_URI} !^/review/` on that rule is the second half of the same guard. Then validate and reload:

   ```bash
   apache2ctl configtest
   systemctl reload apache2
   ```

   The snippet sets `AllowOverride None` for the review document root, so each branch's `public/.htaccess` is ignored
   and its front controller is driven from the snippet instead. Under an `Alias`, Laravel's stock rules have no
   `RewriteBase` and would resolve against the filesystem path rather than `/review/<slug>`.

6. Scope the `SSH_*` CI variables to the `review/*` environment as well as to `staging`, so the review jobs can reach
   the host.

## Lifecycle

`review/review-app.sh` is piped into the host over SSH, so the host always runs the version of it from the commit being
deployed. `deploy` fetches the commit, assigns a port, creates the database, renders `review/review.env.template`
from that checkout into `.env`, runs `scripts/deploy.sh --no-dev --prune-node-modules /review/<slug>`, publishes the
symlink, and restarts the app's user services. `destroy` reverses
all of it, including dropping the database, removing the app's keys from Redis databases 0 (sessions/queues) and 1
(cache), and deleting the checkout. Teardown is self-contained and also works if the checkout is already missing.

Redeploying a branch reuses its port, database and `APP_KEY`, so sessions and data survive.

Stop a review app **before** deleting its branch: the stop job needs to check out the commit it deployed, which GitLab
may no longer be able to provide once the branch is gone. A leftover app can always be removed on the host with:

```bash
REVIEW_SLUG=<slug> review/review-app.sh destroy
```

Run the deployment regression checks locally with:

```bash
nix shell --inputs-from . nixpkgs#{nginx,apacheHttpd,redis,acl,curl,nodejs} --command bash review/test-review-app.sh
```

These use temporary checkouts, nginx, Apache and Redis instances, with database and systemd commands stubbed. They
check redeployment, ACL inheritance, websocket routing and its port/path restrictions, Redis cleanup, and user service
commands. The Apache pass also serves real requests through the symlinked document root, covering the per-branch front
controller, static assets and the dotfile guard; PHP-FPM is out of scope, so front controllers answer as static files.
Any attempt to invoke sudo fails the checks.
