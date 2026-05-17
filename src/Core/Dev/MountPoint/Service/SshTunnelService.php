<?php

declare(strict_types=1);

namespace Aurora\Core\Dev\MountPoint\Service;

use RuntimeException;
use SensitiveParameter;

final readonly class SshTunnelService
{
    public function open(
        string $sshHost,
        int $sshPort,
        string $sshUser,
        #[SensitiveParameter]
        string $privateKeyContent,
        string $remoteHost,
        int $remotePort,
        int $timeout = 10,
    ): SshTunnel {
        $keyFile = tempnam(sys_get_temp_dir(), 'aurora_ssh_');

        // libcrypto requires strict Unix line endings and a trailing newline.
        $normalizedKey = str_replace("\r\n", "\n", $privateKeyContent);
        $normalizedKey = str_replace("\r", "\n", $normalizedKey);
        $normalizedKey = mb_rtrim($normalizedKey)."\n";
        file_put_contents($keyFile, $normalizedKey);
        chmod($keyFile, 0o600);

        $localPort = $this->findFreePort();

        $command = sprintf(
            'ssh -i %s -p %d -L %d:%s:%d %s@%s -N'
            .' -o StrictHostKeyChecking=no'
            .' -o UserKnownHostsFile=/dev/null'
            .' -o ConnectTimeout=%d'
            .' -o BatchMode=yes'
            .' -o ExitOnForwardFailure=yes',
            escapeshellarg($keyFile),
            $sshPort,
            $localPort,
            escapeshellarg($remoteHost),
            $remotePort,
            escapeshellarg($sshUser),
            escapeshellarg($sshHost),
            $timeout,
        );

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes);

        if (false === $process) {
            unlink($keyFile);
            throw new RuntimeException('Failed to start SSH process.');
        }

        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        if (!$this->waitForPort($localPort, $timeout)) {
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_terminate($process);
            proc_close($process);
            unlink($keyFile);

            $reason = $stderr ? ': '.mb_trim($stderr) : '';
            throw new RuntimeException(sprintf('SSH tunnel to %s:%d could not be established within %ds%s', $sshHost, $sshPort, $timeout, $reason));
        }

        return new SshTunnel($process, $pipes, $localPort, $keyFile);
    }

    private function findFreePort(): int
    {
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_bind($socket, '127.0.0.1', 0);
        socket_getsockname($socket, $address, $port);
        socket_close($socket);

        return $port;
    }

    private function waitForPort(int $port, int $timeout): bool
    {
        $deadline = microtime(true) + $timeout;

        while (microtime(true) < $deadline) {
            $socket = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.3);
            if (false !== $socket) {
                fclose($socket);

                return true;
            }

            usleep(200_000);
        }

        return false;
    }
}
