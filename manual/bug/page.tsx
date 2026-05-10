"use client";

import Image from "next/image";
import { useRouter } from "next/navigation";
import styles from "./bug.module.css";

const guidelineItems = [
  "การเฝ้าระวังศัตรูพืชอย่างสม่ำเสมอ ตรวจสอบแปลงนาเพื่อประเมินระดับการระบาดก่อนตัดสินใจใช้วิธีควบคุม",
  "การใช้วิธีป้องกันก่อนเกิดปัญหา เช่น การปลูกพืชหมุนเวียน การเลือกพันธุ์ต้านทาน การจัดการน้ำและดินให้เหมาะสม",
  "การใช้วิธีชีวภาพและวิธีทางกล เช่น การปล่อยแมลงศัตรูธรรมชาติ การใช้กับดัก การกำจัดด้วยมือ",
  "การใช้สารเคมีอย่างมีเหตุผล ใช้เฉพาะเมื่อจำเป็นและเลือกสารที่มีความปลอดภัยสูงต่อผู้ใช้และสิ่งแวดล้อม",
  "การบันทึกข้อมูลการจัดการศัตรูพืช เพื่อใช้ในการวิเคราะห์และปรับปรุงแนวทางในฤดูกาลถัดไป",
];

const navItems = [
  { icon: "🏠", label: "หน้าหลัก", path: "/home", active: true, tone: "green" as const },
  { icon: "☀️", label: "สภาพอากาศ", path: "/weather", active: false, tone: "orange" as const },
  { icon: "🗺️", label: "แผนที่", path: "/map", active: false, tone: "green" as const },
  { icon: "👥", label: "รายงาน", path: "/reports", active: false, tone: "green" as const },
  { icon: "⋯", label: "ตั้งค่า", path: "/settings", active: false, tone: "green" as const },
];

export default function BugPage() {
  const router = useRouter();

  return (
    <div className={styles.page}>
      <header className={styles.header}>
        <button className={styles.backBtn} onClick={() => router.push("/home")} aria-label="กลับหน้าโฮม">
          ←
        </button>
        <h1>การจัดการศัตรูพืช</h1>
      </header>

      <main className={styles.content}>
        <div className={styles.imageWrap}>
          <Image src="/bug.jpg" alt="การจัดการศัตรูพืช" fill priority className={styles.image} />
        </div>

        <section className={styles.card}>
          <h2>แนวทางปฏิบัติ</h2>
          <ul>
            {guidelineItems.map((item) => (
              <li key={item}>{item}</li>
            ))}
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
