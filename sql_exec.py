#!/usr/bin/env python3
"""
Remote SQL execution via sql_exec.php on themed site.

Usage:
    from sql_exec import RemoteSQL
    
    db = RemoteSQL()
    cursor = db.cursor(dictionary=True)
    cursor.execute("SELECT * FROM wp_posts WHERE ID = 123")
    results = cursor.fetchall()
"""

import requests
import json
from typing import List, Dict, Any, Optional

class RemoteCursor:
    """Cursor-like interface for remote SQL execution."""
    
    def __init__(self, remote_sql: 'RemoteSQL', dictionary: bool = False):
        self.remote_sql = remote_sql
        self.dictionary = dictionary
        self._results: List[Any] = []
        self._rowcount: int = 0
        self._lastrowid: Optional[int] = None
    
    def execute(self, sql: str, params: Optional[tuple] = None) -> None:
        """Execute a SQL statement."""
        # Handle parameter binding by escaping and substituting
        if params:
            # Convert %s placeholders to Python format specifiers
            # Then safely escape and substitute values
            safe_params = []
            for p in params:
                if p is None:
                    safe_params.append('NULL')
                elif isinstance(p, (int, float)):
                    safe_params.append(str(p))
                elif isinstance(p, str):
                    # Escape single quotes by doubling them (SQL standard)
                    escaped = p.replace("'", "''").replace("\\", "\\\\")
                    safe_params.append(f"'{escaped}'")
                else:
                    raise ValueError(f"Unsupported parameter type: {type(p)}")
            
            # Replace %s with actual values
            sql_parts = sql.split('%s')
            if len(sql_parts) != len(safe_params) + 1:
                raise ValueError("Parameter count mismatch")
            
            sql = ''.join(
                sql_parts[i] + (safe_params[i] if i < len(safe_params) else '')
                for i in range(len(sql_parts))
            )
        
        result = self.remote_sql._execute(sql)
        
        # Store results (sql_exec.php returns 'rows', not 'results')
        if 'rows' in result:
            self._results = result['rows']
        else:
            self._results = []
        
        self._rowcount = result.get('row_count', 0)
        self._lastrowid = result.get('insert_id')
    
    def fetchall(self) -> List[Any]:
        """Fetch all results."""
        return self._results
    
    def fetchone(self) -> Optional[Any]:
        """Fetch one result."""
        if self._results:
            return self._results[0]
        return None
    
    @property
    def rowcount(self) -> int:
        """Number of affected rows."""
        return self._rowcount
    
    @property
    def lastrowid(self) -> Optional[int]:
        """Last insert ID."""
        return self._lastrowid
    
    def close(self) -> None:
        """Close cursor (no-op for remote)."""
        pass


class RemoteSQL:
    """Remote SQL connection via sql_exec.php."""
    
    # Themed site configuration
    URL = 'https://maxusvanparts.acstestweb.co.uk/sql_exec.php'
    TOKEN = 'maxus-sql-exec-a7f3k9z2-2026'
    
    def __init__(self):
        """Initialize remote SQL connection."""
        self.session = requests.Session()
        self.session.headers.update({'User-Agent': 'MaxusVanParts/RemoteSQL/1.0'})
        # Test connection
        self._test_connection()
    
    def _test_connection(self) -> None:
        """Test connection to sql_exec.php."""
        try:
            result = self._execute("SELECT 1 as test")
            if result.get('rows') != [{'test': '1'}]:
                raise ConnectionError(f"Unexpected test query result: {result}")
        except Exception as e:
            raise ConnectionError(f"Failed to connect to sql_exec.php: {e}")
    
    def _execute(self, sql: str) -> Dict[str, Any]:
        """Execute SQL and return raw result."""
        try:
            response = self.session.post(
                self.URL,
                data={
                    'token': self.TOKEN,
                    'sql': sql
                },
                timeout=300  # 5 minute timeout for long queries
            )
            
            if response.status_code != 200:
                raise Exception(f"HTTP {response.status_code}: {response.text}")
            
            result = response.json()
            
            if 'error' in result:
                raise Exception(result['error'])
            
            return result
            
        except requests.exceptions.RequestException as e:
            raise Exception(f"Request failed: {e}")
        except json.JSONDecodeError as e:
            raise Exception(f"Invalid JSON response: {e}")
    
    def cursor(self, dictionary: bool = False) -> RemoteCursor:
        """Create a cursor for executing queries."""
        return RemoteCursor(self, dictionary=dictionary)
    
    def commit(self) -> None:
        """Commit transaction (no-op - sql_exec.php auto-commits)."""
        pass
    
    def rollback(self) -> None:
        """Rollback transaction (not supported)."""
        raise NotImplementedError("Rollback not supported in RemoteSQL")
    
    def close(self) -> None:
        """Close connection."""
        self.session.close()
    
    def __enter__(self):
        """Context manager entry."""
        return self
    
    def __exit__(self, exc_type, exc_val, exc_tb):
        """Context manager exit."""
        self.close()
