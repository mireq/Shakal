.PHONY: help doc doc-clean clean doxyhack gendoxyphp

help:
	@echo Commands:
	@echo "	make help"
	@echo "	make doc"
	@echo "	make doc-clean"
	@echo "	make clean"
	@echo "	make cscope"
	@echo "	make gendoxyphp"
	@echo "	make doxyhack"

doc:
	doxygen doc/doxygen/Doxyfile
	@echo "--- LOG ---"
	@cat doxygen.log

doxyhack:
	@find . -path './doc/doxyhack' -prune -o -type f -name "*.dox" -exec ln -sf ../../../../../{} ./doc/doxyhack/generated/{} \;
	#@sed -e 's/INPUT\s*=.*/INPUT=.\/doc\/doxyhack\/generated\//g' -e 's/STRIP_FROM_PATH\s*=.*/STRIP_FROM_PATH=.\/doc\/doxyhack\/generated\//g' doc/doxygen/Doxyfile > doc/doxyhack/generated/Doxyfile
	@ln -sf ../../../../../doc/doxygen/Doxyfile doc/doxyhack/generated/doc/doxygen/
	@rm -rfv doc/doxyhack/generated/doc/html
	@ln -s ../../../../doc/html doc/doxyhack/generated/doc/html
	cd doc/doxyhack/generated ;\
	doxygen doc/doxygen/Doxyfile ;\
	echo "--- LOG ---" ;\
	cat doxygen.log

gendoxyphp:
	php doc/doxyhack/filterPhpFiles.php . doc/doxyhack/generated/

doc-clean:
	@find doc/html/ -mindepth 1 -maxdepth 1 ! -name "shared" -exec rm -rfv {} \;
	@rm -fv doxygen.log
	@rm -rfv doc/doxyhack/generated/

clean:doc-clean
	@find . -name "*~" -exec rm -v {} \;
	@rm -fv tags
	@rm -fv cscope.out

cscope:
	@find . -name '*.php' > ./cscope.files
	@cscope -b
	@rm ./cscope.files

ctags:
	@ctags -f tags -R . --exclude=".git" --totals=yes --tag-relative=yes --PHP-kinds=+cf
