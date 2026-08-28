import remote_ssh_helper as r

client = r.get_ssh_client()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && rm -f test_*.php run_full_procurement_test.php get_pid.php inspect_epo.php check_ae_auth.php find_ae_products.php inspect_ae_imports*.php inspect_inventory_sources.php inspect_ae_tables.php inspect_ae_tokens.php")
print("Cleaned temp scripts on remote.")

client.close()
