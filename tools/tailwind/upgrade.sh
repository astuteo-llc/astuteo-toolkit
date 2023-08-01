#!/bin/bash

# Check if class file was provided
if [ "$#" -ne 1 ]; then
    echo "Usage: ./upgrade.sh v1-to-v2"
    exit 1
fi

class_file=$1

# List of directories to scan
dirs="./src ./templates"

while read -r line
do
    old_class=$(echo $line | cut -d ' ' -f 1)
    new_class=$(echo $line | cut -d ' ' -f 2)

    echo "Replacing $old_class with $new_class"

    for dir in $dirs
    do
        if [ -d "$dir" ]
        then
            echo "Processing $dir"
            # Find all files in the directory
            files=$(grep -rl "$old_class" $dir)
            for file in $files
            do
                echo "Updating $file"
                # Use sed to replace old_class with new_class
                sed -i "s/$old_class/$new_class/g" "$file"
            done
        else
            echo "$dir doesn't exist"
        fi
    done

done < "$class_file"
