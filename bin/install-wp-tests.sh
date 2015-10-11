#!/usr/bin/env bash

if [ $# -lt 3 ]; then
	echo "usage: $0 <db-name> <db-user> <db-pass> [db-host] [wp-version] [bp-version]"
	exit 1
fi

DB_NAME=$1
DB_USER=$2
DB_PASS=$3
DB_HOST=${4-localhost}
WP_VERSION=${5-latest}
BP_VERSION=${6-master}

WP_TESTS_DIR=${WP_TESTS_DIR-/tmp/wordpress-tests-lib}
WP_CORE_DIR=${WP_CORE_DIR-/tmp/wordpress}
BP_CORE_DIR=${BP_CORE_DIR-/tmp/wordpress/src/wp-content/plugins/buddypress}

set -ex

download() {
    if [ `which curl` ]; then
        curl -s "$1" > "$2";
    elif [ `which wget` ]; then
        wget -nv -O "$2" "$1"
    fi
}

install_wp() {
	mkdir -p $WP_CORE_DIR

	git clone --depth=1 --branch="$WP_VERSION" git://develop.git.wordpress.org/ $WP_CORE_DIR
}

install_bp() {
	mkdir -p $BP_CORE_DIR

	git clone --depth=1 --branch="$BP_VERSION" git://buddypress.git.wordpress.org/ $BP_CORE_DIR
}

install_test_suite() {
	# portable in-place argument for both GNU sed and Mac OSX sed
	if [[ $(uname -s) == 'Darwin' ]]; then
		local ioption='-i .bak'
	else
		local ioption='-i'
	fi

	# set up testing suite
	#mkdir -p $WP_TESTS_DIR
	cd $WP_CORE_DIR
	#svn co --quiet https://develop.svn.wordpress.org/trunk/tests/phpunit/includes/

	#download https://develop.svn.wordpress.org/trunk/wp-tests-config-sample.php wp-tests-config.php
	cp wp-tests-config-sample.php wp-tests-config.php
	#sed $ioption "s:dirname( __FILE__ ) . '/src/':'$WP_CORE_DIR':" wp-tests-config.php
	sed $ioption "s/youremptytestdbnamehere/$DB_NAME/" wp-tests-config.php
	sed $ioption "s/yourusernamehere/$DB_USER/" wp-tests-config.php
	sed $ioption "s/yourpasswordhere/$DB_PASS/" wp-tests-config.php
	sed $ioption "s|localhost|${DB_HOST}|" wp-tests-config.php
}

install_db() {
	# parse DB_HOST for port or socket references
	local PARTS=(${DB_HOST//\:/ })
	local DB_HOSTNAME=${PARTS[0]};
	local DB_SOCK_OR_PORT=${PARTS[1]};
	local EXTRA=""

	if ! [ -z $DB_HOSTNAME ] ; then
		if [ $(echo $DB_SOCK_OR_PORT | grep -e '^[0-9]\{1,\}$') ]; then
			EXTRA=" --host=$DB_HOSTNAME --port=$DB_SOCK_OR_PORT --protocol=tcp"
		elif ! [ -z $DB_SOCK_OR_PORT ] ; then
			EXTRA=" --socket=$DB_SOCK_OR_PORT"
		elif ! [ -z $DB_HOSTNAME ] ; then
			EXTRA=" --host=$DB_HOSTNAME --protocol=tcp"
		fi
	fi

	# create database
	mysqladmin create $DB_NAME --user="$DB_USER" --password="$DB_PASS"$EXTRA
}

install_wp
install_bp
install_test_suite
install_db
