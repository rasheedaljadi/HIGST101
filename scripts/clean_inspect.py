import remote_ssh_helper as r

client = r.get_ssh_client()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && rm -f inspect_failed_pos.php inspect_po_cols.php inspect_platform_orders.php inspect_po_logs.php inspect_spo_items.php inspect_variant_attrs.php inspect_ae_tables.php inspect_ae_imports.php inspect_import_payload.php inspect_snapshot.php inspect_variants_array.php")
print("Cleaned up temp inspection scripts.")

client.close()
