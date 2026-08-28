import remote_ssh_helper as r

client = r.get_ssh_client()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && rm -f test_nat_addr.php test_ds_extend.php test_explorer_url.php test_whitelist_order.php probe_all_endpoints.php test_trade_buy.php inspect_freight_details.php get_plain_token.php")
print("Cleaned temp files.")

client.close()
