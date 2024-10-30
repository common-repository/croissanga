VERSION=1.3.1

all: croissanga-$(VERSION).zip

clean:
	rm -rf croissanga/
	rm -rf croissanga-$(VERSION).zip

croissanga-$(VERSION).zip: INSTALL LICENSE croissanga.php options/croissanga-db.php options/croissanga-options.php readme.txt
	mkdir croissanga
	mkdir croissanga/options
	cp --parents $^ croissanga
	zip $@ croissanga croissanga/* croissanga/options/*
	rm -rf croissanga
