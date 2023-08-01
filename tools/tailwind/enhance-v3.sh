#!/bin/bash

update_background="./vendor/astuteo/astuteo-toolkit/tools/tailwind/enhance/v3/background-opacity.sh"

# Directories to scan
dirs="./src ./templates"

for dir in $dirs
do
    if [ -d "$dir" ]
    then
        echo "Processing $dir"
        # Find all css, sass, and pcss files in the directory
        files=$(find $dir -type f \( -iname \*.twig -o -iname \*.html -o -iname \*.css -o -iname \*.sass -o -iname \*.pcss \))
        for file in $files
        do
            # Use perl to replace old_class with new_class using regex
            $update_background "$file"
        done
    else
        echo "$dir doesn't exist"
    fi
done
