"use client";

import { useRouter } from "next/navigation";
import styles from "./rice.module.css";
import BackButton from "../../components/BackButton";
import BottomNav from "../../components/BottomNav";

export default function RicePage() {
  const router = useRouter();

  return (
    <div className={styles.container}>
      {/* Header */}
      <div className={styles.header}>
        <BackButton onClick={() => router.push("/home")} className={styles.backBtn} />
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

      <BottomNav activePath="/home" />
    </div>
  );
}
