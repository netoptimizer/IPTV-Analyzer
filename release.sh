#! /bin/sh
#
set -e
NAME=iptables

VERSION=1.4.7
PREV_VERSION=1.4.6

GPGKEY="Netfilter Core Team"

# Create a unique tempdir, to avoid leftovers from older release builds
TMPDIR=`mktemp -dt $NAME.XXXXXXXXXX`
trap 'rm -rf $TMPDIR' EXIT
PKGDIR="$TMPDIR/${NAME}-${VERSION}"

PATCH="patch-$NAME-$PREV_VERSION-$VERSION.bz2";
TARBALL="$NAME-$VERSION.tar.bz2";
CHANGELOG="changes-$NAME-$PREV_VERSION-$VERSION.txt";

#mkdir -p "$TMPDIR"
git shortlog "v$PREV_VERSION..v$VERSION" > "$TMPDIR/$CHANGELOG"
git diff "v$PREV_VERSION..v$VERSION" | bzip2 > "$TMPDIR/$PATCH"
git archive --prefix="$NAME-$VERSION/" "v$VERSION" | tar -xC "$TMPDIR/"

pushd "$PKGDIR" && {
	sh autogen.sh
	popd
}

tar -cjf "$TARBALL" "$NAME-$VERSION";
gpg -u "$GPGKEY" -sb q"$TARBALL";
md5sum "$TARBALL" >"$TARBALL.md5sum";
sha1sum "$TARBALL" >"$TARBALL.sha1sum";

gpg -u "$GPGKEY" -sb "$PATCH";
md5sum "$PATCH" >"$PATCH.md5sum";
sha1sum "$PATCH" >"$PATCH.sha1sum";
