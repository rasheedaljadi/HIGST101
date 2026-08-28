import remote_ssh_helper as r

client = r.get_ssh_client()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && rm -f inspect_spo_93.php debug_spo_93.php inspect_imported_sku_attrs.php check_queue_jobs.php inspect_all_queues.php purge_sync_jobs.php")
print("Cleaned up temp scripts.")

client.close()
