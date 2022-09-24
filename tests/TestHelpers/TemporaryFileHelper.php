<?php

namespace Unleash\Client\Tests\TestHelpers;

trait TemporaryFileHelper
{
    /**
     * @var bool
     */
    private $_tfh_ShutdownFunctionRegistered = false;

    /**
     * @var array<string>
     */
    private $_tfh_DeleteLaterList = [];

    private function _registerShutdownFunction(): void
    {
        if ($this->_tfh_ShutdownFunctionRegistered) {
            return;
        }

        register_shutdown_function(function () {
            foreach ($this->_tfh_DeleteLaterList as $file) {
                if (file_exists($file)) {
                    if (!is_writable($file)) {
                        chmod($file, 0666);
                    }
                    unlink($file);
                }
            }
        });
        $this->_tfh_ShutdownFunctionRegistered = true;
    }

    private function deleteLater(string $filePath): void
    {
        $this->_registerShutdownFunction();
        $this->_tfh_DeleteLaterList[] = $filePath;
    }

    private function createTemporaryFile(bool $autoCleanup = true): string
    {
        $this->_registerShutdownFunction();
        $file = tempnam(sys_get_temp_dir(), 'unleash_tests_file_helper');
        if ($autoCleanup) {
            $this->deleteLater($file);
        }

        return $file;
    }
}
