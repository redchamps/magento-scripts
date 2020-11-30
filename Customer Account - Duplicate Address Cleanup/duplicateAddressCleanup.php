<?php
use Magento\Framework\App\Bootstrap;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;


ini_set('display_errors', 1);
require __DIR__ . '/../app/bootstrap.php';

class DuplicateAddressCleanup extends Command
{
    protected function configure()
    {
        $this->setName('start')
        ->addOption('dry-run', null, InputOption::VALUE_NONE, 'y/n');
        parent::configure();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $isDryRun = $input->getOption('dry-run');
        if (!$isDryRun) {
            $output->writeln('<question>WARNING: this is not a dry run. If you want to do a dry-run, add --dry-run.</question>');
            $question = new ConfirmationQuestion('Are you sure you want to continue? [No] ', false);

            $this->questionHelper = $this->getHelper('question');
            if (!$this->questionHelper->ask($input, $output, $question)) {
                return;
            }
        }
        $params = $_SERVER;

        $bootstrap = Bootstrap::create(BP, $params);
        $obj = $bootstrap->getObjectManager();

        $state = $obj->get('Magento\Framework\App\State');
        $state->setAreaCode('frontend');
        $addressCollection = $obj->get('Magento\Customer\Model\ResourceModel\Address\Collection');
        $customerAddressCollectionFactory = $obj->get('Magento\Customer\Model\ResourceModel\Address\CollectionFactory');
        $orderAddressCollectionFactory = $obj->get('Magento\Sales\Model\ResourceModel\Order\Address\CollectionFactory');
        $quoteAddressCollectionFactory = $obj->get('Magento\Quote\Model\ResourceModel\Quote\Address\CollectionFactory');
        $counter = 1;
        foreach ($addressCollection as $address) {
            $orderAddresses = $orderAddressCollectionFactory->create()
                ->addFieldToFilter("customer_address_id", $address->getId());
            $quoteAddresses = $quoteAddressCollectionFactory->create()
                ->addFieldToFilter("customer_address_id", $address->getId());
            //check if address is being used in any order or quote
            if(!count($orderAddresses) && !count($quoteAddresses)) {
                $duplicateCustomerAddresses = $customerAddressCollectionFactory->create()
                    ->addFieldToFilter("parent_id", $address->getParentId())
                    ->addFieldToFilter('firstname', $address->getFirstname())
                    ->addFieldToFilter('lastname', $address->getLastname())
                    ->addFieldToFilter('street', $address->getStreet())
                    ->addFieldToFilter('postcode', $address->getPostcode());
                if (count($duplicateCustomerAddresses) > 1) {
                    $addressId = $address->getId();
                    $output->writeln(
                        "<comment>$counter. Unused & duplicate customer address id {$addressId} for customer id {$address->getParentId()}</comment>"
                    );
                    if (!$isDryRun) {
                        $address->delete();
                        $output->writeln(
                            "<info>-> Address id {$addressId} has been deleted.</info>"
                        );
                    }
                    $counter++;
                }
            }
        }
        if ($counter == 1) {
            $output->writeln(
                "<comment>No unused duplicate address found.</comment>"
            );
        }
    }
}
//start application
try {
    $instance = new Application();
    $instance->add(new DuplicateAddressCleanup());
    $instance->run();
} catch (Exception $exception) {
    echo "Error: ".$exception->getMessage();
}
