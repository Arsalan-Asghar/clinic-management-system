#!/usr/bin/env python3
"""
=============================================================
  MediCare Clinic — Python Report Generator
  Connects to MySQL, fetches data, generates a text report.

  Requirements:
      pip install mysql-connector-python

  Usage:
      python report.py                   → prints to terminal
      python report.py --save            → saves to report.txt
      python report.py --date 2025-01-15 → report for a date
=============================================================
"""

import sys
import argparse
from datetime import date, datetime

try:
    import mysql.connector
except ImportError:
    print("ERROR: Install mysql-connector-python first.")
    print("       pip install mysql-connector-python")
    sys.exit(1)

# ── DB CONFIG (match your PHP config.php) ─────────────────────
DB_CONFIG = {
    "host":     "localhost",
    "database": "medicare_clinic",
    "user":     "root",
    "password": "",          # change if needed
    "charset":  "utf8mb4",
}

LINE = "─" * 62


def connect():
    """Return a MySQL connection."""
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        return conn
    except mysql.connector.Error as e:
        print(f"Connection failed: {e}")
        sys.exit(1)


def fetch_stats(cursor) -> dict:
    cursor.execute("SELECT COUNT(*) FROM patients")
    patients = cursor.fetchone()[0]

    cursor.execute("SELECT COUNT(*) FROM doctors")
    doctors = cursor.fetchone()[0]

    cursor.execute("SELECT COUNT(*) FROM appointments")
    total_appts = cursor.fetchone()[0]

    cursor.execute(
        "SELECT COUNT(*) FROM appointments WHERE appt_date = CURDATE()"
    )
    today_appts = cursor.fetchone()[0]

    cursor.execute(
        "SELECT COUNT(*) FROM appointments WHERE status = 'Completed'"
    )
    completed = cursor.fetchone()[0]

    return {
        "patients":     patients,
        "doctors":      doctors,
        "total_appts":  total_appts,
        "today_appts":  today_appts,
        "completed":    completed,
    }


def fetch_appointments(cursor, target_date: str) -> list:
    query = """
        SELECT
            a.appt_time,
            p.name  AS patient,
            d.name  AS doctor,
            d.specialty,
            a.reason,
            a.status
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        JOIN doctors  d ON a.doctor_id  = d.id
        WHERE a.appt_date = %s
        ORDER BY a.appt_time ASC
    """
    cursor.execute(query, (target_date,))
    return cursor.fetchall()


def fetch_top_doctors(cursor) -> list:
    query = """
        SELECT d.name, d.specialty, COUNT(a.id) AS total
        FROM doctors d
        LEFT JOIN appointments a ON a.doctor_id = d.id
        GROUP BY d.id, d.name, d.specialty
        ORDER BY total DESC
        LIMIT 5
    """
    cursor.execute(query)
    return cursor.fetchall()


def fetch_recent_patients(cursor) -> list:
    cursor.execute(
        "SELECT name, age, gender, blood_group, created_at "
        "FROM patients ORDER BY created_at DESC LIMIT 5"
    )
    return cursor.fetchall()


def build_report(target_date: str) -> str:
    conn   = connect()
    cursor = conn.cursor()

    lines = []
    now   = datetime.now().strftime("%Y-%m-%d %H:%M:%S")

    lines.append(LINE)
    lines.append("       MEDICARE CLINIC — MANAGEMENT REPORT")
    lines.append(f"       Generated : {now}")
    lines.append(f"       Report For: {target_date}")
    lines.append(LINE)

    # ── Overview stats ──────────────────────────────────────
    stats = fetch_stats(cursor)
    lines.append("")
    lines.append("  OVERVIEW STATISTICS")
    lines.append("  " + "─" * 40)
    lines.append(f"  Total Registered Patients : {stats['patients']}")
    lines.append(f"  Total Doctors             : {stats['doctors']}")
    lines.append(f"  Total Appointments (all)  : {stats['total_appts']}")
    lines.append(f"  Today's Appointments      : {stats['today_appts']}")
    lines.append(f"  Completed Appointments    : {stats['completed']}")

    # ── Today's schedule ────────────────────────────────────
    appts = fetch_appointments(cursor, target_date)
    lines.append("")
    lines.append(f"  APPOINTMENTS ON {target_date}  ({len(appts)} total)")
    lines.append("  " + "─" * 40)

    if not appts:
        lines.append("  No appointments found for this date.")
    else:
        lines.append(
            f"  {'TIME':<8} {'PATIENT':<20} {'DOCTOR':<22} {'STATUS':<12}"
        )
        lines.append("  " + "─" * 60)
        for row in appts:
            time_str = str(row[0])[:5]                  # HH:MM
            patient  = (row[1] or "")[:18]
            doctor   = (row[2] or "")[:20]
            status   = row[5] or "Scheduled"
            lines.append(
                f"  {time_str:<8} {patient:<20} {doctor:<22} {status:<12}"
            )

    # ── Top doctors by appointments ──────────────────────────
    top_docs = fetch_top_doctors(cursor)
    lines.append("")
    lines.append("  TOP DOCTORS BY APPOINTMENT COUNT")
    lines.append("  " + "─" * 40)
    lines.append(f"  {'RANK':<6} {'DOCTOR':<25} {'SPECIALTY':<20} {'APPTS'}")
    lines.append("  " + "─" * 56)
    for i, row in enumerate(top_docs, 1):
        name      = (row[0] or "")[:23]
        specialty = (row[1] or "")[:18]
        total     = row[2]
        lines.append(f"  {i:<6} {name:<25} {specialty:<20} {total}")

    # ── Recent patients ──────────────────────────────────────
    recent = fetch_recent_patients(cursor)
    lines.append("")
    lines.append("  RECENTLY REGISTERED PATIENTS (last 5)")
    lines.append("  " + "─" * 40)
    lines.append(f"  {'NAME':<22} {'AGE':<5} {'GENDER':<8} {'BLOOD':<6}")
    lines.append("  " + "─" * 44)
    for row in recent:
        name   = (row[0] or "")[:20]
        age    = row[1]
        gender = (row[2] or "")[:6]
        blood  = row[3] or "N/A"
        lines.append(f"  {name:<22} {age:<5} {gender:<8} {blood:<6}")

    lines.append("")
    lines.append(LINE)
    lines.append("  End of Report")
    lines.append(LINE)

    cursor.close()
    conn.close()
    return "\n".join(lines)


def main():
    parser = argparse.ArgumentParser(description="Clinic Report Generator")
    parser.add_argument(
        "--date", default=str(date.today()),
        help="Target date (YYYY-MM-DD). Defaults to today."
    )
    parser.add_argument(
        "--save", action="store_true",
        help="Save report to clinic_report.txt"
    )
    args = parser.parse_args()

    report = build_report(args.date)
    print(report)

    if args.save:
        filename = f"clinic_report_{args.date}.txt"
        with open(filename, "w", encoding="utf-8") as f:
            f.write(report)
        print(f"\n  Report saved to: {filename}")


if __name__ == "__main__":
    main()
