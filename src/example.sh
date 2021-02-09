#!/bin/bash
# Ask the user for login details
# shellcheck disable=SC2162
read -p 'Project: ' project
read -p 'Database: ' localdb
read -p 'User: ' localuser
read -sp 'Password: ' passvar

# sed -e "${"localdb"}" -e "${"passvar"}" -e "${"localuser"}" .env.text | tee .env.auto
rm -rf ./astuteo-temp/
git clone https://github.com/astuteo-llc/build-config.git ./astuteo-temp/

sed -e 's/${project-name}/'$project'/g' ./astuteo-temp/reference/project-package.json | tee -a package.json


echo Copying Mix config
cp ./astuteo-temp/example.webpack.mix.js ./webpack.mix.js
cp ./astuteo-temp/example.webpack.mix.js ./webpack.mix.js
mkdir ./config
mv ./astuteo-temp/reference/config/mix ./config
cp ./astuteo-temp/reference/.nvmrc ./.nvmrc
cp ./astuteo-temp/reference/.browserslistrc ./.browserslistrc

while true; do
    read -p "Use Tailwind? [y/n] " yn
    case $yn in
        [Yy]* ) npm install tailwindcss@latest; break;;
        [Nn]* ) break;;
        * ) echo "Please answer yes (y) or no (n).";;
    esac
done

while true; do
    read -p "Use Alpine.js? [y/n] " yn
    case $yn in
        [Yy]* ) npm i alpinejs; break;;
        [Nn]* ) break;;
        * ) echo "Please answer yes (y) or no (n).";;
    esac
done

while true; do
    read -p "Include Scripts? [y/n] " yn
    case $yn in
        [Yy]* )
          mv ./astuteo-temp/reference/scripts/ ./scripts

          echo Remote Server Information
          read -p 'ssh (user@ip): ' remoteLogin
          read -p 'db user: ' remoteDbUser
          read -p 'db password: ' remoteDbPassword
          read -p 'db name: ' remoteDbName
          sed -e 's/${remoteLogin}/'$remoteLogin'/g' -e 's/${passvar}/'$passvar'/g' -e 's/${remoteDbName}/'$remoteDbName'/g' -e 's/${remoteDbPassword}/'$remoteDbPassword'/g' -e 's/${remoteDbUser}/'$remoteDbUser'/g' -e 's/${remoteDbPassword}/'$remoteDbPassword'/g' ./scripts/example.local.env.sh | tee -a ./scripts/.env.sh
        break;;
        [Nn]* ) break;;
        * ) echo "Please answer yes (y) or no (n).";;
    esac
done
while true; do
    read -p "Add bin/deploy? [y/n] " yn
    case $yn in
        [Yy]* )
          read -p 'Server PHP version (e.g. 7.3): ' phpVersion
          mkdir ./bin
          sed -e  's/${phpVersion}/'$phpVersion'/g' ./astuteo-temp/reference/bin/deploy | tee ./bin/deploy
          cp ./astuteo-temp/reference/config/deploy.conf ./config/example.deploy.conf
        break;;
        [Nn]* ) break;;
        * ) echo "Please answer yes (y) or no (n).";;
    esac
done

while true; do
    read -p "Add src boilerplate? [y/n] " yn
    case $yn in
        [Yy]* )
          mv ./astuteo-temp/reference/src/* ./src
        break;;
        [Nn]* ) break;;
        * ) echo "Please answer yes (y) or no (n).";;
    esac
done

while true; do
    read -p "Add example templates? [y/n] " yn
    case $yn in
        [Yy]* )
          mv ./astuteo-temp/reference/templates/* ./templates
        break;;
        [Nn]* ) break;;
        * ) echo "Please answer yes (y) or no (n).";;
    esac
done

echo
echo Local configuration has been added or updated