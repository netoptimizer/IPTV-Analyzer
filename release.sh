#! /bin/bash
#
# Script for making releases, where you are forced to create a git tag
# before you can make a release.  If you want tarballs without using a
# git tag, then use the make tarball system.
#
set -e
NAME=iptv-analyzer
VERSION=HEAD
PREV_VERSION=0.7.0

echo "Creating tarball for release: $NAME-$VERSION"

#VERSION_TAG="v{$VERSION}"
VERSION_TAG=HEAD #HACK for testing

GPGKEY="D0777D99"

# Create a unique tempdir, to avoid leftovers from older release builds
TMPDIR=`mktemp -dt $NAME.XXXXXXXXXX`
#trap 'rm -rf $TMPDIR' EXIT
echo TMPDIR:$TMPDIR
PKGDIR="$TMPDIR/${NAME}-${VERSION}"
echo PKGDIR:$PKGDIR
RELDIR=release
if [ ! -d $RELDIR ]; then
    mkdir -p $RELDIR
fi

# Compression packer tool
packer=gzip
packext=gz

PATCH="$RELDIR/patch-$NAME-$PREV_VERSION-$VERSION.$packext";
TARBALL="$RELDIR/$NAME-$VERSION.tar.$packext";
CHANGES="changes-$NAME-$PREV_VERSION-$VERSION.txt";

#mkdir -p "$TMPDIR"
git shortlog "v$PREV_VERSION..$VERSION_TAG" > "$RELDIR/$CHANGES"
git diff "v$PREV_VERSION..$VERSION_TAG" | $packer > "$PATCH"
git archive --prefix="$NAME-$VERSION/" "$VERSION_TAG" | tar -xC "$TMPDIR/"

pushd "$PKGDIR" && {
	sh autogen.sh
	popd
}

tar --use=${packer} -C "$TMPDIR" -cf "$TARBALL" "$NAME-$VERSION";
gpg -u "$GPGKEY" -sb "$TARBALL";
md5sum "$TARBALL" >"${TARBALL}.md5sum";
sha1sum "$TARBALL" >"${TARBALL}.sha1sum";

gpg -u "$GPGKEY" -sb "$PATCH";
md5sum "$PATCH" >"${PATCH}.md5sum";
sha1sum "$PATCH" >"${PATCH}.sha1sum";
