"use client";

import Image from "next/image";
import { useRouter } from "next/navigation";
import styles from "./harvest.module.css";

const navItems = [
  { icon: "🏠", label: "หน้าหลัก", path: "/home", active: true, tone: "green" as const },
  { icon: "☀️", label: "สภาพอากาศ", path: "/weather", active: false, tone: "orange" as const },
  { icon: "🗺️", label: "แผนที่", path: "/map", active: false, tone: "green" as const },
  { icon: "👥", label: "รายงาน", path: "/reports", active: false, tone: "green" as const },
  { icon: "⋯", label: "ตั้งค่า", path: "/settings", active: false, tone: "green" as const },
];

export default function HarvestPage() {
  const router = useRouter();

  return (
    <div className={styles.page}>
      <header className={styles.header}>
        <button className={styles.backBtn} onClick={() => router.push("/home")} aria-label="กลับหน้าโฮม">
          ←
        </button>
        <h1>การเก็บเกี่ยวและการปฏิบัติหลังการเก็บเกี่ยว</h1>
      </header>

      <main className={styles.content}>
        <div className={styles.imageWrap}>
          <Image src="/harvest.jpg" alt="การเก็บเกี่ยวข้าว" fill priority className={styles.image} />
        </div>

        <section className={styles.card}>
          <h2>แนวทางปฏิบัติ</h2>
          <ul>
            <li>กำหนดเวลาเก็บเกี่ยวอย่างเหมาะสม เก็บเกี่ยวเมื่อข้าวสุกเต็มที่เพื่อให้ได้ผลผลิตที่มีคุณภาพสูงสุด</li>
            <li>ใช้เครื่องมือหรืออุปกรณ์ที่เหมาะสม เช่น รถเกี่ยวข้าว เครื่องนวดข้าว หรืออุปกรณ์เก็บเกี่ยวที่ลดการแตกหักของเมล็ด</li>
            <li>
              การจัดการหลังการเก็บเกี่ยว
              <ul className={styles.subList}>
                <li>การทำความสะอาดเมล็ดข้าว</li>
                <li>การลดความชื้นอย่างเหมาะสม (เช่น การอบแห้ง)</li>
                <li>การคัดแยกเมล็ดที่เสียหายหรือคุณภาพต่ำ</li>
              </ul>
            </li>
            <li>การจัดเก็บอย่างถูกวิธี ใช้ภาชนะหรือคลังเก็บที่ป้องกันความชื้น แมลง และเชื้อรา</li>
            <li>การบันทึกข้อมูลผลผลิต เช่น ปริมาณข้าวที่เก็บเกี่ยว วันที่เก็บเกี่ยว และคุณภาพเมล็ดข้าว</li>
          </ul>
        </section>
      </main>

      <nav className={styles.bottomNav}>
        {navItems.map((item) => (
          <button
            key={item.path}
            className={`${styles.navItem} ${item.active ? styles.active : ""} ${
              item.tone === "orange" ? styles.orange : styles.green
            }`}
            onClick={() => router.push(item.path)}
          >
            <span className={styles.icon}>{item.icon}</span>
            <span>{item.label}</span>
          </button>
        ))}
      </nav>
    </div>
  );
}
