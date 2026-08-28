import remote_ssh_helper as r

client = r.get_ssh_client()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && rm -f inspect_batch_failures.php test_submit_spo.php check_po_72.php debug_preflight_80.php test_direct_gateway.php test_active_po.php")
print("Cleaned up temp test scripts.")

client.close()
