# Tailwind CSS Upgrade Helper

This bash script will help you to upgrade your Tailwind CSS classes in your project files when moving from one major version to another.

## Running the Upgrade Script

The `upgrade.sh` script requires a single argument: the path to a text file that maps old class names to new ones. The script and the class mapping file are both located in the `vendor/astuteo/astuteo-toolkit/tools/tailwind` directory of your Craft CMS project.

For example, to run the upgrade script with the `v1-to-v2.txt` file provided:

```bash
./vendor/astuteo/astuteo-toolkit/tools/tailwind/upgrade.sh ./vendor/astuteo/astuteo-toolkit/tools/tailwind/v1-to-v2.txt
 ```

For v2 to v3 upgrade:

```bash 
./vendor/astuteo/astuteo-toolkit/tools/tailwind/upgrade.sh ./vendor/astuteo/astuteo-toolkit/tools/tailwind/v2-to-v3.txt
```

This will start the script, which will begin replacing the old class names with new ones in your project files. The script scans all files in the `src` and `templates` directories relative to your project root. It looks for `.css`, `.sass`, and `.pcss` file types in the `src` directory, and all file types in the `templates` directory.

Please make sure to test your application thoroughly after running the script to ensure everything still works as expected.

## Custom Class Changes

You can also create your own text file for custom class changes. This file should be a plain text file with each line representing a class change. Each line should have the old class name followed by a space, then the new class name.

Here's an example of what this file might look like:

```
old-class new-class
another-old-class another-new-class
yet-another-old-class yet-another-new-class
```

To use this file, you would run the script as follows:

```
./vendor/astuteo/astuteo-toolkit/tools/tailwind/upgrade.sh ./path/to/examplefile.txt
```

Replace `./path/to/examplefile.txt` with the actual relative path to your custom class change file.

## Enhancement Helper
Occasionally Tailwind will add new approaches to an existing class. As an example, from v2 to v3 bg-black bg-opacity-50 can be replaced with bg-black/50. This script will help you find these instances and replace them with the new syntax.
To use this file, you would run the script as follows:

For v2 to v3 upgrade run:
```
./vendor/astuteo/astuteo-toolkit/tools/tailwind/enhance-v3.sh
```

For best results, it is recommended to run prettier or similar to order the classes in a more predictable way.

## Notes

- Always ensure your files are under version control or otherwise backed up before running this script, as it modifies your files in place without creating a backup.
- If you need to adjust the directories that the script scans or the file types it looks for, you can modify the `dirs` and `types` variables at the top of the script.
