#!/bin/sh
mkdir -p ezpublish_legacy/var/storage/packages/eZ-systems
cd ezpublish_legacy/var/storage/packages/eZ-systems

wget http://packages.ez.no/ezpublish/5.4/5.4.0/ezdemo_site.ezpkg
wget http://packages.ez.no/ezpublish/5.3/5.3.0/ezwt_extension.ezpkg
wget http://packages.ez.no/ezpublish/5.3/5.3.0/ezstarrating_extension.ezpkg
wget http://packages.ez.no/ezpublish/5.3/5.3.0/ezgmaplocation_extension.ezpkg
wget http://packages.ez.no/ezpublish/5.3/5.3.0/ezflow_extension.ezpkg
wget http://packages.ez.no/ezpublish/5.4/5.4.0/ezdemo_extension.ezpkg
wget http://packages.ez.no/ezpublish/5.4/5.4.0/ezdemo_classes.ezpkg
wget http://packages.ez.no/ezpublish/5.4/5.4.0/ezdemo_democontent.ezpkg
wget http://packages.ez.no/ezpublish/5.4/5.4.0/ezdemo_democontent_clean.ezpkg

for PATHNAME in *.ezpkg; do

  BASENAME=$(basename $PATHNAME .ezpkg)
  echo "> $BASENAME"
  mkdir $BASENAME
  cd $BASENAME
  tar zxf ../$PATHNAME
  cd ..
  rm $PATHNAME

done;
