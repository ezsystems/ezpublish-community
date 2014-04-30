#!/bin/bash

###############################################################################
# This script adds the possibility of testing travis with dependent branches
# on different repositories (this happens a lot with BDD)
#
# For example:
#    Implementing a feature test on DemoBundle that have implementation on
#    both DemoBundle context file and Browser context of Community, to test
#    on Travis it needs to have both branches
###############################################################################

### Private Vars ###
KERNEL=0
LEGACY=1
DEMO=2
COMMUNITY=3

GIT_URL="https://github.com/<USER>/<REPOSITORY>.git"

REPOSITORY[$KERNEL]="ezpublish-kernel"
REPOSITORY[$LEGACY]="ezpublish-legacy"
REPOSITORY[$DEMO]="demobundle"
REPOSITORY[$COMMUNITY]="ezpublish-community"
### end of private vars ###

### Define the testing repositories and branches ###
MASTER_USER="ezsystems"
MASTER_LOCATION="vendor/ezsystems"

# Repositories
USER[$KERNEL]=$MASTER_USER
USER[$LEGACY]=$MASTER_USER
USER[$DEMO]=$MASTER_USER
USER[$COMMUNITY]=$MASTER_USER

# Branches
BRANCH[$KERNEL]=""
BRANCH[$LEGACY]=""
BRANCH[$DEMO]=""
BRANCH[$COMMUNITY]=""

# Locations
LOCATION[$KERNEL]="$MASTER_LOCATION/ezpublish-kernel"
LOCATION[$LEGACY]="ezpublish_legacy"
LOCATION[$DEMO]="$MASTER_LOCATION/demobundle/EzSystems/DemoBundle"
LOCATION[$COMMUNITY]="."

### Set Array with changing branches ###
INSTALL[0]="KERNEL"
INSTALL[1]="LEGACY"
INSTALL[2]="DEMO"
INSTALL[3]="COMMUNITY"

### Error checking ###
function doCommand
{
    # execute command
    $@

    # verify if it failed
    if [ $? -ne 0 ]; then
        echo "> Last command failed: $@"
        echo "> exiting..."
        exit 1
    fi
}

### Changing Branches ###
for REPO in ${INSTALL[@]}; do
    # check if branch is set
    if [ "${BRANCH[${REPO}]}" != "" ]; then
        URL=${GIT_URL/<USER>/${USER[${REPO}]}}
        URL=${URL/<REPOSITORY>/${REPOSITORY[${REPO}]}}

        # if it's community we can't remove because of being the root of project
        # so we need to add the remote stream and change to branch of that stream
        if [ "${REPO}" == "COMMUNITY" ]; then
            doCommand git remote add ${USER[${REPO}]}_stream $URL --track ${BRANCH[${REPO}]}
            doCommand git fetch ${USER[${REPO}]}_stream --depth 1
            doCommand git checkout ${BRANCH[${REPO}]}

        # other wise we just remove the folder clone the remote, and change to
        # branch
        else
            echo "> Changing bundle:'${REPO}'"
            echo "- User: ${USER[${REPO}]}"
            echo "- Repository: ${REPOSITORY[${REPO}]}"
            echo "- Branch: ${BRANCH[${REPO}]}"
            doCommand rm -rf ${LOCATION[${REPO}]}
            doCommand git clone $URL ${LOCATION[${REPO}]} --depth 1 --single-branch --branch ${BRANCH[${REPO}]}
            doCommand cd ${LOCATION[${REPO}]}
            doCommand cd -
            echo "- done: '${REPO}'"
            echo ""
        fi
    fi
done
