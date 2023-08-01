#!/bin/bash

# Check if class file was provided
if [ "$#" -ne 1 ]; then
    echo "Usage: ./upgrade.sh v1-to-v2"
    exit 1
fi

class_file=$1

# List of directories to scan
dirs="./src ./templates"

# File types to scan in the src directory
types="css sass scss pcss"

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
            # Differentiate between ./src and ./templates
            if [ "$dir" == "./src" ]
            then
                for type in $types
                do
                    echo "Looking for $type files"
                    # Find all files of the specific type in the directory
                    files=$(find $dir -name "*.$type" -print0 | xargs -0 grep -l "$old_class")
                    for file in $files
                    do
                        echo "Updating $file"
                        # Use sed to replace old_class with new_class
                        sed -i '' "s/$old_class/$new_class/g" "$file"
                    done
                done
            else
                files=$(grep -rl "$old_class" $dir)
                for file in $files
                do
                    echo "Updating $file"
                    # Use sed to replace old_class with new_class
                    sed -i '' "s/$old_class/$new_class/g" "$file"
                done
            fi
        else
            echo "$dir doesn't exist"
        fi
    done

done < "$class_file"
