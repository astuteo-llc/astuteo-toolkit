#!/bin/bash
# https://tailwindcss.com/docs/upgrade-guide#new-opacity-modifier-syntax
# File to process
file=$1

echo "New opacity modifier syntax: $file"
# Use perl to replace old_class with new_class using regex
perl -i -pe 's/(bg-)([-\w]+)(\s+)(bg-opacity-)(\d+)/$1$2\/$5/g' "$file"
