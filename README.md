# ASU Digital Repository on Islandora8
This repository is a drupal root for ASU Digital Repository built using [Islandora](https://islandora.ca/). ([Islandora Documentation](https://islandora.github.io/documentation/))

For development purposes, this repository should be integrated with the [islandora provided vagrant environment](https://github.com/Islandora-Devops/claw-playbook).

It will also include ansible scripts for provisioning and deploying to additional environments.

# Configuration Management

The site uses the config_split module to separate out deployment-specific configurations and secrets. The base configurations for KEEP and PRISM are in `config/sync` and `config/prism`, respectively. Each also has site-specific configuration directories, `config/env_keep` and `config/env_prism` which *are not* included in the Git repository and need to be added manually. You can view the config_split configuration files for [KEEP](config/sync/config_split.config_split.env_keep.yml) and [PRISM](config/prism/config_split.config_split.env_prism.yml) to see what is currently included.

Two other splits, "Development Config" and "AWS Deployment", need to be enabled using the sites' settings.php file. E.g. for AWS deployment, add `$config['config_split.config_split.aws_deployment']['status'] = TRUE;`. 
Most of the configurations for both of these are included in the Git repository. The one exception, in AWS Deployment, is `smtp.settings.yml` needs to be added manually. E.g.

```yml
smtp_on: true
smtp_host: FILL IN HOST
smtp_hostbackup: ''
smtp_port: 'FILL IN PORT'
smtp_protocol: tls
smtp_autotls: true
smtp_timeout: 60
smtp_username: FILL IN USERNAME
smtp_password: FILL IN PASSWORD
smtp_from: digitalrepository@asu.edu
smtp_fromname: 'ASU Digital Repository'
smtp_client_hostname: ''
smtp_client_helo: ''
smtp_allowhtml: '1'
smtp_test_address: ''
smtp_debugging: false
prev_mail_system: php_mail
smtp_keepalive: false
```

# Local Development Setup - *Not currently in use*
1. Install dependencies
    a. VirtualBox version 5.whatever (not 6.0)
    b. Vagrant (tested up to version 2.1.2)
    c. git
    d. ansible
    e. vagrant vbguest plugin (`vagrant plugin install vagrant-vbguest`)
2. Go to the [ASU claw-playbook repo](https://github.com/asulibraries/claw-playbook)
3. Clone ASU claw-playbook
4. cd into claw-playbook
5. Make a file called in your user root called .asurepo_vault_pass and get it from the lastpass. (this is the password for decrypting ansible vault stuff which will allow you to deploy to create and encrypt files)
6. Run `vagrant up` (from within the claw-playbook root) - it will default to using the asurepo basebox (modelled after the islandora/8 base box idea). If you want to run the build explicitly (via ansible), change the ISLANDORA_DISTRO like `ISLANDORA_DISTRO="ubuntu/bionic64" vagrant up`
7. This repo will be available inside the vagrant VM as `/var/www/html/drupal`
8. Make sure composer modules are up to date `composer install` from `/var/www/html/drupal`
9. make sure submodules are ready `git submodule init` and `git submodule update` from `/var/www/html/drupal`
10. If you want the ASU specific config, cd into `/var/www/html/drupal` and run `drupal config:import --directory /var/www/html/drupal/config/sync`
11. Depending on the environment - do a config import of that environment like `drush config:import --partial --source /var/www/html/drupal/config/dev`

## ASU Basebox Option
If you are using the ASU basebox option and want to spin a new box, then you can simply run `vagrant up` and skip all of the following steps about composer installing and updating modules and database stuff because that will be a fully up to date box (assuming Eli has kept it up to date).
Another thing worth noting is that a new basebox will need to be updated with `vagrant box update` - note that box updates can only be performed on new installs or destroyed VMs (not on halted/suspended VMs).


# Local theme development
Note: I only have gotten this working on my local machine, not the vagrant environment yet.-dlf

Requirements:
* node.js
(if you do not have node, do `sudo apt update` and `curl -sL https://deb.nodesource.com/setup_14.x | sudo -E bash -
sudo apt-get install -y nodejs` `sudo apt install npm`

1. cd into `/web/themes/custom/asulib_barrio`
2. Install gulp with `npm install --global gulp-cli`
3. Install yarn with `npm install --global yarn`
4. add `@asu-design-system:registry=https://registry.web.asu.edu` to your `~/.npmrc`
5. run `npm adduser --registry https://registry.web.asu.edu` and make a user
6. Install dependencies including Bootstrap latest version: `yarn install`
7. cd into the theme then `node_modules/@asu-design-system/bootstrap4-theme` and `npm install`
8. Update `gulpfile.js` with your local URL

Example:

    browserSync.init({
        proxy: 'http://localhost:8000/',
    })
9). Run `gulp`

"This will generate a style.css file with mappings for debugging and a style.min.css file for production. You will need to change the reference to the file manually on your SUBTHEME.libraries.yml file."

Instructions are from https://www.drupal.org/docs/8/themes/bootstrap-4-sass-barrio-starter-kit/installation

# Ansible
If you've already provisioned your vagrant environment and need to re-run the ASU specific provisioning, you can do so with `ansible-playbook asu-install.yml -i inventory/vagrant -l all -e ansible_ssh_user=$vagrantUser -e islandora_distro=elizoller/asurepo` Your $vagrantUser will either be ubuntu or vagrant. Check to see what user you become when you `vagrant ssh`. The default playbook now uses a prebuilt base box (called elizoller/asurepo). If we want to build explicitly, we need to specify that our VM requires the ubuntu base box instead so we can customize. We do this by prefixing the `ISLANDORA_DISTRO="ubuntu/bionic64"` to the front of `vagrant up` and `vagrant provision` calls.


# Helpful Hints
If you need to update your ansible roles (to get updated versions of the packages), you mine as well `rm -rf roles/external` and `ISLANDORA_DISTRO="ubuntu/bionic64" vagrant provision` to fix that. This will take some time.

Understanding how drupal entities relate to fedora objects - https://drive.google.com/file/d/1Ra64mFAsHkPtAf-2BWjdYKDv1Fc2uJSU/view

Get the json-ld for an object in Drupal like so : http://localhost:8000/node/1?_format=jsonld

# Updating an existing install
1. pull down updated code and configs (`cd /var/www/html/drupal && git pull`)
2. Make sure composer modules are up to date `composer install` from `/var/www/html/drupal`
3. make sure submodules are ready `git submodule init` and `git submodule update` from `/var/www/html/drupal`
4. drupal config:import for each site like `drush --uri=https://site-url config:import`
5. cd into web directory
6. run database migrations for each site - `drush --uri=https://site-url updatedb`
7. clear drupal cache for each site - `drush --uri=https://site-url cache-rebuild`


## So you want to add a module
1. Add the module to the composer requirements in the ASU specific ansible role
2. Add the module to the drush enabling in the ASU specific ansible role
3. Run the ASU specific ansible role

## Tips on Config Syncing
* To export content, go to your drupal root such as `/var/www/html/drupal` and run `drush --uri=https://site-url cex`
* To import content, go to your drupal root such as `/var/www/html/drupal` and run `drush --uri=https://site-url cim`
* To import new configurations from a different directory (such as a new migraiton), use `drush --uri=https://site-url cim --partial --source=/the/path/`

## Tips on Using Drush
[Drush full command list](https://drushcommands.com/drush-9x/)
Common Commands
* `drush cache-rebuild` - clear cache
* `drush pm:enable module_name` - enable module
* `drush pm:uninstall module_name` - disable module

## Tips on Using Composer
* To install everything from a composer.json file - `composer install`
* To add a package `composer require packagename`
* To update a package `composer update packagename`


# Deploying to AWS - *Not currently in use*
1. `pip install boto boto3`
2. run `ansible-playbook aws_create_multiple_ec2.yml`
3. locally run `ansible-galaxy install -r requirements.yml`
4. locally run `ansible-playbook -i inventory/stage playbook.yml -e "islandora_distro=ubuntu/bionic64" -e @inventory/stage/group_vars/all/passwords.yml -e @aws_keys.yml`
<!-- must have an IAM role and key with privileges to administer EC2 -->
- The approach I've taken thus far is to create 2 EC2 instances in the following breakdown:
  - webserver - for the actual drupal site, cantaloupe
  - services - for karaf, alpaca, crayfish, fedora, cantaloupe, blazegraph, solr
- There are two RDS databases connected as well: for drupal and fedora
- The ideal state might look something like: https://www.lucidchart.com/invitations/accept/8a83a394-5cf6-48c8-9434-6803456c283a
- For the time being, I've set up separate security groups for each EC2 instance to allow inbound traffic on the required ports from various locations (such as ASU IPs and the other EC2 instances)
- All EC2 instances have static Elastic Block volumes associated with them (8GB each)
- The webserver also has a related S3 bucket (asulibdev-islandora-bucket) which is currently being used for islandora_bagger to send preservation bags. It has a automatic rule to push to Glacier after 30 days of inactivity.
- An RDS MYSQL instance has also been provisioned and connection is allowed to the webserver for the purpose of hosting the drupal database. In the future, additional RDS instances can be created for the gemini database, matomo database. (The Riprap database is currently being integrated with the Drupal database). You can connect to the RDS instance from the webserver EC2 instance manually like `mysql -u drupal8 -p -h islandora-drupal.cvznsvixsvec.us-west-2.rds.amazonaws.com --port 3306`
- If configuration changes have been made, you'll need to import config/sync and then do a partial import on the config for that env

# Updating existing components
## Islandora modules
`composer update drupal/module_name`

# Component Glossary and Notes
(in alphabetical order)

## [Alpaca](https://github.com/Islandora-CLAW/Alpaca)
Apache Camel middleware which listens to events emitted from Drupal and distributes them to the microservices. ASU fork is [here](https://github.com/asulibraries/alpaca).

## Api-X
https://github.com/fcrepo4-labs/fcrepo-api-x/blob/master/src/site/markdown/apix-design-overview.md

## [Blazegraph](https://www.blazegraph.com/)
A high performance graph database, aka the triplestore


## [Carapace](https://github.com/Islandora-CLAW/carapace)
A Drupal theme for Islandora, based on [AdaptiveTheme](https://www.drupal.org/project/adaptivetheme)

## [Cantaloupe](https://github.com/cantaloupe-project/cantaloupe)
A IIIF compliant image server, written in Java

## [Chullo](https://github.com/Islandora-CLAW/chullo)
A PHP client which directly communicates with the Fedora 5 API. ASU fork is [here](https://github.com/asulibraries/chullo)

## [ClamAV](https://www.clamav.net/)
A virus scanning application.
If you get an error in the Drupal Status report saying that it couldn't connect to ClamAV, likely the service isn't running.
1. SSH to the VM `vagrant ssh`
2. `sudo service clamav-freshclam status`
3. If its down, restart it `sudo service clamav-freshclam restart` or if its up, proceed to the next step. Note that sometimes it needs to be up for 1 minute before proceeding to the next step.
4. `sudo service clamav-daemon status` Likely this will tell you it is down. If freshclam is running, it needs to get the updated ClamAV Virus Database (.cvd) file(s) from freshclam before the daemon can be started.
5. `sudo service clamav-daemon restart`

## [Crayfish](https://github.com/Islandora-CLAW/Crayfish)
A collection of micro-services: Gemini, Homarus, Houdini, Hypercube, Milliner, and Recast. ASU fork is [here](https://github.com/asulibraries/Crayfish)

## [Controlled Access Terms](https://github.com/Islandora/controlled_access_terms)
An Islandora module that adds vocabularies and fields to allowed controlled vocabulary usage in Islandora. The most significant of these being the "linked agent" field with a custom "Typed Relation" field type.

## [Crayfish-commons](https://github.com/Islandora/Crayfish-Commons)
Shared code for the Crayfish microservices

## Fedora

## Fits

## Flysystem
- https://github.com/Islandora-CLAW/CLAW/blob/master/docs/technical-documentation/flysystem.md
- Component which allows persistance of binary files in Drupal to actually occur in fedora

## Gemini
https://github.com/Islandora-CLAW/Crayfish/tree/master/Gemini
- Mapping service from Drupal UUID to Fedora URI
enable JWT Authentication Issuer module
To use any of the API endpoints or Gemini, you need a JWT token - which can be generated with a request like `curl -i -u admin:islandora http://localhost:8000/jwt/token`.
ie. ```curl -X GET \
  http://localhost:8000/gemini/4c82e5c1-73bb-402d-ab3c-e6e1d49fa9f9 \
  -H 'Authorization: Bearer tokenhere' '```

## Homarus
## Houdini
## Hypercube

## Karaf
log file is in /opt/karaf/data/log


## OpenSeaDragon
IIIF-compliant image viewer
https://github.com/Islandora-CLAW/openseadragon

## Microservices

## Milliner
## Recast
## Syn
