"use client";

import Image from "next/image";
import { useRouter } from "next/navigation";
import styles from "./disease.module.css";

const diseaseCards = [
  {
    title: "เพลี้ยกระโดดสีน้ำตาล",
    leftImage: "/weed.jpg",
    rightImage: "/bug.jpg",
  },
  {
    title: "โรคไหม้ข้าว",
    leftImage: "/soil.jpg",
    rightImage: "/rice1.jpg",
  },
];

const navItems = [
  { icon: "🏠", label: "หน้าหลัก", path: "/home", active: true, tone: "green" as const },
  { icon: "☀️", label: "สภาพอากาศ", path: "/weather", active: false, tone: "orange" as const },
  { icon: "🗺️", label: "แผนที่", path: "/map", active: false, tone: "green" as const },
  { icon: "👥", label: "รายงาน", path: "/reports", active: false, tone: "green" as const },
  { icon: "⋯", label: "ตั้งค่า", path: "/settings", active: false, tone: "green" as const },
];

export default function DiseasePage() {
  const router = useRouter();

  return (
    <div className={styles.page}>
      <header className={styles.header}>
        <button className={styles.backBtn} onClick={() => router.push("/home")} aria-label="กลับหน้าโฮม">
          ←
        </button>
        <h3>โรคข้าว</h3>
      </header>

      <main className={styles.content}>
        {diseaseCards.map((card) => (
          <article key={card.title} className={styles.card}>
            <div className={styles.imageRow}>
              <div className={styles.imageBox}>
                <Image src={card.leftImage} alt={card.title} fill className={styles.image} />
              </div>
              <div className={styles.imageBox}>
                <Image src={card.rightImage} alt={card.title} fill className={styles.image} />
              </div>
            </div>

            <h2>{card.title}</h2>

            <button className={styles.detailButton} type="button">
              <Image src="/file.svg" alt="" width={24} height={24} aria-hidden />
              รายละเอียด
            </button>
          </article>
        ))}
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
