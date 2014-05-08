#!/bin/bash

###############################################################################
# This script adds the possibility of testing travis with dependent branches
# on different repositories (this happens a lot with BDD)
#
# For example:
#    Implementing a feature test on DemoBundle that have implementation on
#    both DemoBundle context file and Browser context of Community, to test
#    on Travis it needs to have both branches
#
# How to use this:
#   So for the example we want to use "johnDoe" "kernel" repository with the
#   "some-test-branch" branch
#   So ALL changes should be made in the "Define the variables at your will" section
#   Then change the lines:
#       from                                to
#       MASTER_USER="ezsystems"             MASTER_USER="johnDoe"
#       BRANCH[$KERNEL]=""                  BRANCH[$KERNEL]="some-test-branch"
#   And it's done
#
# To know:
#   The script wont do nothing unless a branch is defined, even if you intend
#   to use "master" branch it needs to be specified
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

### Define the variables at your will ###
MASTER_USER="mloureiro"
MASTER_LOCATION="vendor/ezsystems"

# Repositories
USER[$KERNEL]=$MASTER_USER
USER[$LEGACY]=$MASTER_USER
USER[$DEMO]=$MASTER_USER
USER[$COMMUNITY]=$MASTER_USER

# Branches
BRANCH[$KERNEL]="fix_sw_legacy#948"
BRANCH[$LEGACY]=""
BRANCH[$DEMO]=""
BRANCH[$COMMUNITY]=""

# Locations
LOCATION[$KERNEL]="$MASTER_LOCATION/ezpublish-kernel"
LOCATION[$LEGACY]="ezpublish_legacy"
LOCATION[$DEMO]="$MASTER_LOCATION/demobundle/EzSystems/DemoBundle"
LOCATION[$COMMUNITY]="."
### end of User Variables ###

### Array with changing branches ###
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
        echo "> Changing bundle:'${REPO}'"
        echo "- User: ${USER[${REPO}]}"
        echo "- Repository: ${REPOSITORY[${REPO}]}"
        echo "- Branch: ${BRANCH[${REPO}]}"

        # if it's community we can't remove because of being the root of project
        # so we need to add the remote stream and change to branch of that stream
        if [ "${REPO}" == "COMMUNITY" ]; then
            doCommand git remote add ${USER[${REPO}]}_stream $URL --track ${BRANCH[${REPO}]}
            doCommand git fetch ${USER[${REPO}]}_stream --depth 1
            doCommand git checkout ${BRANCH[${REPO}]}

        # other wise we just remove the folder clone the remote, and change to
        # branch
        else
            doCommand rm -rf ${LOCATION[${REPO}]}
            doCommand git clone $URL ${LOCATION[${REPO}]} --depth 1 --single-branch --branch ${BRANCH[${REPO}]}
        fi
        
        echo "- done: '${REPO}'"
        echo ""
    fi
done
