#! /bin/bash
#
# Script for making releases, where you are forced to create a git tag
# before you can make a release.  If you want tarballs without using a
# git tag, then use the make tarball system.
#
set -e
NAME=iptv-analyzer
VERSION=0.9.1
PREV_VERSION=0.9.0
GPGKEY="D0777D99"

echo "Creating tarball for release: $NAME-$VERSION"
echo "============================="

# Create a unique tempdir, to avoid leftovers from older release builds
TMPDIR=`mktemp -dt $NAME.XXXXXXXXXX`
trap 'rm -rf $TMPDIR' EXIT
#echo TMPDIR:$TMPDIR
PKGDIR="$TMPDIR/${NAME}-${VERSION}"
#echo PKGDIR:$PKGDIR
RELDIR=release
if [ ! -d $RELDIR ]; then
    mkdir -p $RELDIR
fi
VERSION_TAG="v${VERSION}"
#VERSION_TAG=HEAD #HACK for testing

# Compression packer tool
packer=gzip
packext=gz

#PATCH="$RELDIR/patch-$NAME-$PREV_VERSION-$VERSION.$packext";
TARBALL="$RELDIR/$NAME-$VERSION.tar.$packext";
CHANGES="$RELDIR/changes-$NAME-$PREV_VERSION-$VERSION.txt";

#mkdir -p "$TMPDIR"
echo " -- Git shortlog v$PREV_VERSION..$VERSION_TAG"
git shortlog "v$PREV_VERSION..$VERSION_TAG" > "$CHANGES"
#git diff "v$PREV_VERSION..$VERSION_TAG" | $packer > "$PATCH"
echo " -- Git archiving version tag $VERSION_TAG"
git archive --prefix="$NAME-$VERSION/" "$VERSION_TAG" | tar -xC "$TMPDIR/"

pushd "$PKGDIR" > /dev/null && {
    echo " -- Generating configure scripts..."
    sh autogen.sh
    popd > /dev/null
}

echo " -- Creating tarball $TARBALL"
tar --use=${packer} -C "$TMPDIR" -cf "$TARBALL" "$NAME-$VERSION";

echo " -- Calculating checksums"
md5sum "$TARBALL" >"${TARBALL}.md5sum";
sha1sum "$TARBALL" >"${TARBALL}.sha1sum";

echo " -- You need to sign the tarball"
gpg -u "$GPGKEY" -sb "$TARBALL";

#gpg -u "$GPGKEY" -sb "$PATCH";
#md5sum "$PATCH" >"${PATCH}.md5sum";
#sha1sum "$PATCH" >"${PATCH}.sha1sum";
