<?php

declare(strict_types=1);

namespace Aurora\Module\Dev\MountPoint\Service;

final readonly class SshTunnel
{
    /**
     * @param resource   $process
     * @param resource[] $pipes
     */
    public function __construct(
        private mixed $process,
        private array $pipes,
        public int $localPort,
        private string $keyFile,
    ) {}

    public function close(): void
    {
        foreach ($this->pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }

        if (is_resource($this->process)) {
            proc_terminate($this->process);
            proc_close($this->process);
        }

        if (file_exists($this->keyFile)) {
            unlink($this->keyFile);
        }
    }
}
