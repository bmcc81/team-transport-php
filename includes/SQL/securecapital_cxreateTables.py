import os
from frm_reader import read_frm

# Path to your database folder containing .frm files
db_folder = r"C:\xampp\mysql\data\securecapita"

# Output file for all DDL statements
output_file = r"C:\xampp\mysql\data\securecapita_ddl.sql"

ddl_statements = []

for file in os.listdir(db_folder):
    if file.endswith(".frm"):
        frm_path = os.path.join(db_folder, file)
        table_name = file.replace(".frm", "")
        try:
            table = read_frm(frm_path)
            ddl = table.create_table_statement()
            ddl_statements.append(f"{ddl};\n")
            print(f"[OK] Extracted DDL for table `{table_name}`")
        except Exception as e:
            print(f"[ERROR] Could not read `{table_name}`: {e}")

# Save all extracted DDL to a file
with open(output_file, "w", encoding="utf-8") as f:
    f.writelines(ddl_statements)

print(f"\nAll DDL statements saved to: {output_file}")