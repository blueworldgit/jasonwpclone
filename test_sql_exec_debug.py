#!/usr/bin/env python3
"""Debug remote SQL connection."""
import requests
import json

URL = 'https://maxusvanparts.acstestweb.co.uk/sql_exec.php'
TOKEN = 'maxus-sql-exec-a7f3k9z2-2026'

print("Testing remote SQL endpoint...")
print(f"URL: {URL}")
print()

response = requests.post(
    URL,
    data={
        'token': TOKEN,
        'sql': 'SELECT 1 as test'
    },
    timeout=30
)

print(f"Status: {response.status_code}")
print(f"Headers: {dict(response.headers)}")
print()
print(f"Raw response:")
print(response.text)
print()

if response.status_code == 200:
    try:
        result = response.json()
        print(f"Parsed JSON:")
        print(json.dumps(result, indent=2))
    except json.JSONDecodeError as e:
        print(f"JSON decode error: {e}")
