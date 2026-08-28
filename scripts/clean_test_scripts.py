import remote_ssh_helper as r

client = r.get_ssh_client()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && rm -f test_trade_extra_nat.php query_sa_order.php check_warehouse_src.php update_inv_src.php test_gateway_e2e.php")
print("Cleaned test scripts on remote.")

client.close()
