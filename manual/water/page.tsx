"use client";

import Image from "next/image";
import { useRouter } from "next/navigation";
import styles from "./water.module.css";

const guidelineItems = [
  "วางแผนการใช้น้ำล่วงหน้า เช่น การกำหนดช่วงเวลาที่ต้องการน้ำมากที่สุด และการจัดสรรน้ำตามฤดูกาล",
  "ใช้เทคนิคการปลูกแบบเปียกสลับแห้ง (AWD) เพื่อประหยัดน้ำและลดการปล่อยก๊าซมีเทนจากนาข้าว",
  "ตรวจสอบระดับน้ำในแปลงนาอย่างสม่ำเสมอ เพื่อปรับการให้น้ำให้เหมาะสมกับระยะการเจริญเติบโตของข้าว",
  "หลีกเลี่ยงการปล่อยน้ำทิ้งโดยไม่ผ่านการกรองหรือบำบัด เพื่อป้องกันการปนเปื้อนของสารเคมีหรือตะกอนสู่แหล่งน้ำธรรมชาติ",
  "บันทึกการใช้น้ำในแต่ละรอบการผลิต เพื่อใช้ในการวิเคราะห์และปรับปรุงการจัดการในอนาคต",
];

const navItems = [
  { icon: "🏠", label: "หน้าหลัก", path: "/home", active: true },
  { icon: "☀️", label: "อากาศ", path: "/weather", active: false },
  { icon: "🗺️", label: "แผนที่", path: "/map", active: false },
  { icon: "👥", label: "รายงาน", path: "/reports", active: false },
  { icon: "⚙️", label: "ตั้งค่า", path: "/settings", active: false },
];

export default function WaterPage() {
  const router = useRouter();

  return (
    <div className={styles.page}>
      <header className={styles.header}>
         <button
            className={styles.backBtn}
            onClick={() => router.push("/home")}>
            ←
          </button>
        <h3>การใช้น้ำ</h3>
      </header>

      <main className={styles.content}>
        <div className={styles.imageWrap}>
          <Image src="/water.jpg" alt="การใช้น้ำในแปลงนา" fill priority className={styles.image} />
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
        {navItems.map((item) => {
          return (
            <button
              key={item.path}
              className={`${styles.navItem} ${item.active ? styles.active : ""}`}
              onClick={() => router.push(item.path)}
            >
              <span className={styles.icon}>{item.icon}</span>
              <span>{item.label}</span>
            </button>
          );
        })}
      </nav>
    </div>
  );
}
