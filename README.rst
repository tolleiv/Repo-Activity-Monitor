=====================================
Visualize the TYPO3 Core activity.... 
=====================================

*Disclaimer: These scripts are very "hacky" and I never thought that the results would ever be so nice and worth beeing published. I'm really ashamed that I didn't go the TDD way here.*

**License: I don't care what you do with it -> MIT license (see license.txt)**

These steps are required to get it working
------------------------------------------

* Get a fresh copy of the TYPO3 Core and it's submodules ( see `wiki.typo3.org <http://wiki.typo3.org/Git_Clone>`_ )
* Clone this repository to some place
* Run ``php core-stat.php /path/to/your/core/checkout /path/for/the/output`` within the checkout of that repo (Might take some time).
* Find some files containing the rendered statistics in ``/path/for/the/output``
* Copy `ìndex.html``, `graph.js``, ``raphael.js``, ``ajax-loader.gif``, ``style.css``from the repo also into ``/path/for/the/output``

To get it working with other repositories
-----------------------------------------

* Check the ``core-stat.php`` and change ``$modules`` to use the proper submodule paths (if you've any). 
* Check the ``core-stat.php`` and change the ``$stateDate`` to have a reasonable value
* Check ``StatGenerator::mapName``within ``core-lib.php`` and adjust the names to fit your repo.
* Remove my flattr buttons and Google Analytics stuff from the source-codes ;)

Sidenotes
---------

The script holds "old" commit data in a local cache ( ``stat.inc`` ) - that's mainly to avoid duplicate processing of commit history. It's odd to have this file laying around with data when programming - so better delete it before you check your modifications.

Have fun with it and share your results!
