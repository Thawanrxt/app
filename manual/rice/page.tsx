"use client";

import styles from "./rice.module.css";
import Link from "next/link";

export default function RicePage() {
  return (
    <div className={styles.container}>
      {/* Header */}
      <div className={styles.header}>
        <Link href="/home" className={styles.backBtn}>←</Link>
        <h2>พันธุ์ข้าว</h2>
      </div>

      {/* ข้าวเจ้า */}
      <section>
        <h3 className={styles.sectionTitle}>ข้าวเจ้า</h3>

        <div className={styles.cardGrid}>
          <div className={styles.card}>
            <img src="/harvest.jpg" alt="ข้าวกข105" />
            <h4>ข้าวดอกมะลิ105</h4>
            <p>เหมาะสำหรับปลูกในพื้นที่ลุ่ม</p>
            <button>รายละเอียด</button>
          </div>

          <div className={styles.card}>
            <img src="/harvest.jpg" alt="ข้าวปทุมธานี 1" />
            <h4>ข้าวปทุมธานี 1</h4>
            <p>ต้านทานโรค ผลผลิตดี</p>
            <button>รายละเอียด</button>
          </div>
        </div>
      </section>

      {/* ข้าวเหนียว */}
      <section>
        <h3 className={styles.sectionTitle}>ข้าวเหนียว</h3>

        <div className={styles.cardGrid}>
          <div className={styles.card}>
            <img src="/harvest.jpg" alt="เหนียว8974" />
            <h4>เหนียว 8974</h4>
            <p>เมล็ดสวย เหนียวนุ่ม</p>
            <button>รายละเอียด</button>
          </div>

          <div className={styles.card}>
            <img src="/harvest.jpg" alt="ข้าวเหนียวดำ" />
            <h4>ข้าวเหนียวดำ</h4>
            <p>คุณค่าทางอาหารสูง</p>
            <button>รายละเอียด</button>
          </div>
        </div>
      </section>
    </div>
  );
}