#!/bin/bash

locales=( de en )
groups=( auth calendar legend login modal navigation privacy_statement quota site_credits toast user_happening )
target=$1

if [ -z "${target}" ]; then
    echo "Target dir is not set.";
    exit 1
else
    echo "Target dir is set to '$target'";
fi

for locale in "${locales[@]}"
do
	for group in "${groups[@]}"
	do

		echo "php artisan lang-export:csv -l $locale -g $group --delimiter=';' --output=${target}${group}_${locale}.csv"
		php artisan lang-export:csv -l $locale -g $group --delimiter=';' --output=${target}${group}_${locale}.csv
	done
done

