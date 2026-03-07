#!/usr/bin/env python3
"""
Remove categories from T90 EV products that shouldn't be there.

T90 EV is an ELECTRIC vehicle, so it should NOT have:
- Air Intake System
- Alternative Hvac
- Communicate
- Emission Exhaust System
- Fuel Storage & Handling
- Power Energy Storage & Link Wire
- Power Generation
- Powertrain Control & Diagnostic
- Rearinterior Hvac Airflow
- Sealant & Body Attachment

These are diesel/fuel engine categories that don't belong on electric vehicles.
