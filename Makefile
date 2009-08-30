.PHONY: help doc doc-clean clean

help:
	@echo Commands:
	@echo "	make help"
	@echo "	make doc"
	@echo "	make doc-clean"
	@echo "	make clean"

doc:
	doxygen doc/doxygen/Doxyfile

doc-clean:
	@find doc/html/ -mindepth 1 -maxdepth 1 ! -name "shared" -exec rm -rfv {} \;
	@rm -fv doxygen.log

clean:doc-clean
	@find . -name "*~" -exec rm -v {} \;
