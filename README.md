### First iteration of basic ee commands
- create
	wp ee site create one.com --wp --letscncrypt
- info (Shows site details in tabular form)
	wp ee site info one.com
- show (Shows site config details)
	wp ee site show one.com
- list
	wp ee site list
- update
	wp ee site update one.com --wpfc
- delete
	wp ee site delete one.com

Note: This is a the first iteration of the code and logic which can be improved throughout development.
There are no separation of concerns in the sense that the logic of commands and database are in a single file.

A better implementation will be done if this iteration clears the test.