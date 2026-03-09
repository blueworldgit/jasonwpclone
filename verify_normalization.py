#!/usr/bin/env python3
import mysql.connector
db=mysql.connector.connect(host='localhost',user='root',password='',database='maxussql')
c=db.cursor()

c.execute('SELECT COUNT(*) FROM wp_postmeta WHERE meta_key="attribute_pa_variant" AND meta_value REGEXP "^(left|right)-[bc][0-9]"')
print(f'Products with left-/right- pattern remaining: {c.fetchone()[0]}')

c.execute('SELECT COUNT(*) FROM wp_postmeta WHERE meta_key="attribute_pa_variant" AND meta_value IN ("Left", "Right")')
print(f'Products with "Left" or "Right" (normalized): {c.fetchone()[0]}')

db.close()
print('\n✅ Normalization verification complete')
