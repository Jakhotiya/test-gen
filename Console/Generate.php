<?php
declare(strict_types=1);
namespace Jakhotiya\TestGen\Console;


use Magento\Framework\Code\Reader\ClassReader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;


class Generate extends Command
{


    protected function configure()
    {
        $description = 'Generates unit tests for a given class.';

        $this->setName('test:generate')->setDescription($description);
        $this->setDefinition([
            new InputArgument(
                'class',
                InputArgument::REQUIRED,
                'Class name'
            )
        ]);
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $om = \Magento\Framework\App\ObjectManager::getInstance();
        $class = $input->getArgument('class');
        if(!class_exists($class)){
            $output->writeln("<error>Class $class does not exist</error>");
            return -1;
        }
        $moduleName = $this->getModuleFromClass($class);
        $moduleDir = $om->get(\Magento\Framework\Module\Dir::class);
        $dir =  $moduleDir->getDir($moduleName);
        $io =  new \Jakhotiya\TestGen\Code\Generator\Io(new \Magento\Framework\Filesystem\Driver\File(), $dir);

        $generator =  $om->create(\Jakhotiya\TestGen\Code\Generator\Test::class,[
            'sourceClassName' => $class,
            'resultClassName' => $this->getTestClassName($class),
            'ioObject' => $io,
        ]);



        $generator->generate();
        if(count($generator->getErrors())){
            $output->writeln('Errors: ');
            foreach($generator->getErrors() as $error){
                $output->writeln('<error>'.$error.'</error>');
            }
        }

        $output->writeln('Done');
    }

    private function getTestClassName($class){
        $class = ltrim($class,'\\');

        $parts = explode('\\', $class);
        $vendorName = array_shift($parts);
        $module = array_shift($parts);
        array_unshift($parts, $vendorName, $module, 'Test', 'Unit');
        $parts[count($parts)-1] = $parts[count($parts)-1] . 'Test';
        return implode('\\', $parts);
    }

    private function getModuleFromClass($class){
        $class = ltrim($class,'\\');
        $parts = explode('\\', $class);
        return $parts[0] . '_' . $parts[1];
    }

}

