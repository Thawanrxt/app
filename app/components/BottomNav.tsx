"use client";

import { useRouter } from "next/navigation";
import styles from "./BottomNav.module.css";

const NAV_ITEMS = [
  { icon: "🏠", label: "หน้าหลัก", path: "/home" },
  { icon: "☀️", label: "อากาศ", path: "/weather" },
  { icon: "🗺️", label: "แผนที่", path: "/map" },
  { icon: "👥", label: "รายงาน", path: "/reports" },
  { icon: "⚙️", label: "ตั้งค่า", path: "/settings" },
];

export default function BottomNav({ activePath }: { activePath: string }) {
  const router = useRouter();

  return (
    <nav className={styles.bottomNav}>
      {NAV_ITEMS.map((item) => (
        <button
          key={item.path}
          className={`${styles.navItem} ${activePath === item.path ? styles.active : ""}`}
          onClick={() => router.push(item.path)}
        >
          <span className={styles.navIcon}>{item.icon}</span>
          <span>{item.label}</span>
        </button>
      ))}
    </nav>
  );
}
