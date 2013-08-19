# ownCloud on OpenShift from scratch

Patterned liberally from Isaac Christoffersen's [OpenShift quickstart](https://github.com/ichristo/owncloud-openshift-quickstart).

This assumes that you have a directory called `projects`.

    mkdir -p ~/projects && cd ~/projects

## Create app

    rhc app create owncloud php-5.3 mysql-5.1
    rhc alias add owncloud <your.alias.goes.here>
    rhc app show owncloud
    rm owncloud/php/*.php

## Get ownCloud

    mkdir ~/projects/owncloud-dist && cd ~/projects/owncloud-dist
    wget http://download.owncloud.org/community/owncloud-5.0.10.tar.bz2
    tar xjvf owncloud-5.0.10.tar.bz2
    cd owncloud
    rsync -av . ~/projects/owncloud/php/

## Add script to auto-configure ownCloud

In `~/projects/owncloud/php/config/autoconfig.php`:

    <?php
        define("DIRECTORY",$_SERVER['OPENSHIFT_DATA_DIR'] );
        define("DBNAME",$_SERVER['OPENSHIFT_APP_NAME'] );
        define("DBUSER",$_SERVER['OPENSHIFT_MYSQL_DB_USERNAME'] );
        define("DBPASS",$_SERVER['OPENSHIFT_MYSQL_DB_PASSWORD'] );
        define("DBHOST",$_SERVER['OPENSHIFT_MYSQL_DB_HOST'] . ':' . $_SERVER['OPENSHIFT_MYSQL_DB_PORT'] );
        $AUTOCONFIG = array(
            'installed' => false,
            'dbtype' => 'mysql',
            'dbtableprefix' => 'oc_',
            'adminlogin' => 'admin',
            'adminpass' => 'OpenShiftAdmin',
            'directory' => DIRECTORY,
            'dbname' => DBNAME,
            'dbuser' => DBUSER,
            'dbpass' => DBPASS,
            'dbhost' => DBHOST
        );
    ?>

## Add OpenShift-specific action hooks

Add the following [action
hooks](https://www.openshift.com/developers/deploying-and-building-applications)
to be executed at specific points in the
deployment process of the OpenShift app. The scripts are located in
`~/projects/owncloud/.openshift/action_hooks`.

    * **pre_build** - Restores an existing configuration file and removes the
      autoconfig script above for succeeding pushes.
    * **deploy** - Ensures that the MySQL cartridge is available.
    * **post_deploy** - During the first push, it will autoconfigure the
      ownCloud instance. For succeeding deployments, it will ensure that an
      existing configuration is used.

## Deploy ownCloud

    cd ~/projects/owncloud
    git add .
    git commit -a -m "Deploy to OpenShift"
    git push

## Log in and start using ownCloud on OpenShift

Go to `https://<your.alias.goes.here>`, and use `admin/OpenShiftAdmin` as the
default first-time credentials. (Make sure to change this ASAP.)

## Optional: Use cron

If you use ownCloud apps that require background jobs that need to run
regularly, add OpenShift's Cron cartridge.

    rhc cartridge-add cron-1.4 -a owncloud

Cron jobs go in `~/projects/owncloud/.openshift/cron/` under the respective
periods.

For example, for the News app (an RSS reader), add the following job that runs
every 15 minutes (in `~/projects/owncloud/.openshift/cron/minutely/owncloud.sh`):


    #!/bin/bash

    if [[ -f  $OPENSHIFT_REPO_DIR/php/cron.php ]] ; then
        if [[ $(( $(date +%M) % 15 )) -eq 0 ]] ; then
            printf "{\"app\":\"Cron\",\"message\":\"%s\",\"level\":1,\"time\":%s}\n" "Running cron job" $(date +%s) >> $OPENSHIFT_DATA_DIR/owncloud.log
            pushd $OPENSHIFT_REPO_DIR/php &> /dev/null
            php -f cron.php
	        if [[ $? -ne 0 ]] ; then
        	    printf "{\"app\":\"Cron\",\"message\":\"%s\",\"level\":2,\"time\":%s}\n" "Error running cron job" $(date +%s) >> $OPENSHIFT_DATA_DIR/owncloud.log
        	fi
            popd &> /dev/null
        fi
    fi

Make sure to enable the system cron in ownCloud's `Admin` settings page.
