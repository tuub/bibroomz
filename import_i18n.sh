#!/bin/bash

locales=( de en )
groups=( auth calendar legend login modal navigation privacy_statement quota site_credits toast user_happening )
target="/srv/git/roomz/lang/"

# Due to fixed directories in lang-import:
# Create symlink from resources/lang to lang
ln -sfn ${PWD}/lang ${PWD}/resources/lang

for locale in "${locales[@]}"
do
	for group in "${groups[@]}"
	do
		echo "php artisan lang-import:csv --delimiter=';' ${target}${group}_${locale}.csv"
		php artisan lang-import:csv --delimiter=';' ${target}${group}_${locale}.csv
	done
done

# Delete symlink from resources/lang to lang
rm ${PWD}/resources/lang
