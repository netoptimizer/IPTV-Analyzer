#! /bin/sh
#
set -e
NAME=iptables

VERSION=1.4.7
PREV_VERSION=1.4.6

# Create a unique tempdir, to avoid leftovers from older release builds
TMPDIR=`mktemp -dt $NAME.XXXXXXXXXX`
trap 'rm -rf $TMPDIR' EXIT
IPTDIR="$TMPDIR/${NAME}-${VERSION}"

PATCH="patch-iptables-$PREV_VERSION-$VERSION.bz2";
TARBALL="iptables-$VERSION.tar.bz2";
CHANGELOG="changes-iptables-$PREV_VERSION-$VERSION.txt";

#mkdir -p "$TMPDIR"
git shortlog "v$PREV_VERSION..v$VERSION" > "$TMPDIR/$CHANGELOG"
git diff "v$PREV_VERSION..v$VERSION" | bzip2 > "$TMPDIR/$PATCH"
git archive --prefix="iptables-$VERSION/" "v$VERSION" | tar -xC "$TMPDIR/"

cd "$IPTDIR" && {
	sh autogen.sh
	cd ..
}

tar -cjf "$TARBALL" "iptables-$VERSION";
gpg -u "Netfilter Core Team" -sb "$TARBALL";
md5sum "$TARBALL" >"$TARBALL.md5sum";
sha1sum "$TARBALL" >"$TARBALL.sha1sum";

gpg -u "Netfilter Core Team" -sb "$PATCH";
md5sum "$PATCH" >"$PATCH.md5sum";
sha1sum "$PATCH" >"$PATCH.sha1sum";
