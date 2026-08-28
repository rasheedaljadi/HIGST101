import remote_ssh_helper as r

client = r.get_ssh_client()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && rm -f read_error_log.php check_b72.php test_batch_submit_72.php check_b72_state.php run_submit_72.php")
print("Cleaned up temp scripts.")

client.close()
