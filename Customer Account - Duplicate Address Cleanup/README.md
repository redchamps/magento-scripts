# Customer Account - Duplicate Address Cleanup  - Magento 2 Script 

The Magento 2 script will allow deleting duplicate addresses from customer account.

The process to check & delete addresses is:

1. It will loop through all the customer addresses
2. Check if address is being used for any existing order or quote
3. If not then it will check if this address is duplicate of some other address in the customer account
4. If found true then it will delete the address

## How to use the script

1. Put the script file "duplicateAddressCleanup.php" in any subdirectory at Magento root directory (For example, create a new directory named "scripts" at the root directory & place the file inside it)
2. Run command `php <subdirectory-name>/duplicateAddressCleanup.php --dry-run` to only view the list of duplicate addresses
3. Run command `php <subdirectory-name>/duplicateAddressCleanup.php` to delete the duplicate addresses
