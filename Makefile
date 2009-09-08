.PHONY: help doc doc-clean clean

help:
	@echo Commands:
	@echo "	make help"
	@echo "	make doc"
	@echo "	make doc-clean"
	@echo "	make clean"
	@echo "	make cscope"

doc:
	doxygen doc/doxygen/Doxyfile
	@echo "--- LOG ---"
	@cat doxygen.log

doc-clean:
	@find doc/html/ -mindepth 1 -maxdepth 1 ! -name "shared" -exec rm -rfv {} \;
	@rm -fv doxygen.log

clean:doc-clean
	@find . -name "*~" -exec rm -v {} \;
	@rm -v tags
	@rm -v cscope.out

cscope:
	@find . -name '*.php' > ./cscope.files
	@cscope -b
	@rm ./cscope.files

ctags:
	@ctags -f tags -R . --exclude=".git" --totals=yes --tag-relative=yes --PHP-kinds=+cf
