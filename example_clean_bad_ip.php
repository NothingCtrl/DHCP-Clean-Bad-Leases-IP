<?php
$server_name = 'srv.demo.local';
$dhcp_scope = ['192.168.1.0'];

cleanBadIpAdress($server_name, $dhcp_scope);

/**
 * Clean bad ip address leases
 * https://social.technet.microsoft.com/Forums/windowsserver/en-US/53a1e987-5d44-415e-8510-62cb1e58716a/weird-mac-on-dhcp-31202e3235332e302e?forum=winserverNIS
 * @param string $server_name, dhcp server name, ex: srv.domain.local
 * @param array $dhcp_scope, scopes by dhcp, ex: ['192.168.1.0']
 */
function cleanBadIpAdress($server_name, $dhcp_scope)
{
    if (!empty($dhcp_scope)) {
        foreach ($dhcp_scope as $scope) {
            $export_file_name = "{$server_name}.{$scope}.txt";
            // refresh address leases list
            exec("netsh dhcp server scope {$scope} initiate reconcile fix");
            echo "Refresh DHCP leases IP done" . PHP_EOL;
            // export address leases to file, in the same folder of php file
            exec("netsh dhcp server \\\\{$server_name} scope {$scope} show clients 1 > {$export_file_name}");
            echo "Export DHCP leases IP to file done" . PHP_EOL;
            // read file and find bad ip
            if (file_exists($export_file_name)) {
                $content = explode(PHP_EOL, file_get_contents($export_file_name));
                if (!empty($content)) {
                    foreach ($content as $line) {
                        $line = explode("  ", $line);
                        // $line[2] is subnet and $line[3] is have mac address string
                        if (isset($line[2]) && isset($line[3]) && strpos($line[2], '255.') !== false) {
                            if (strlen($line[3]) > 20) {
                                // found bad mac address
                                $bad_ip = $line[0];
                                echo "Found bad ip {$bad_ip}, mac address {$line[3]}" . PHP_EOL;
                                // delete bad address
                                exec("netsh dhcp server scope {$scope} delete lease {$bad_ip}");
                                echo "Badd IP {$bad_ip} deleted" . PHP_EOL;
                            }
                        }
                    }
                }
                // delete exported file
                unlink($export_file_name);
            }
        }
    }
}