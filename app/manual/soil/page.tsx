"use client";

import { useRouter } from "next/navigation";
import Image from "next/image";
import styles from "./soil.module.css";
import BackButton from "../../components/BackButton";
import BottomNav from "../../components/BottomNav";

export default function SoilPage() {
  const router = useRouter();

  return (
    <div className={styles.wrapper}>
      <div className={styles.container}>

        {/* Header */}
        <div className={styles.header}>
          <BackButton onClick={() => router.push("/home")} className={styles.backBtn} />
          การจัดการดิน
        </div>

        {/* Image */}
        <div className={styles.imageBox}>
          <Image src="/soil.jpg" alt="soil" fill priority style={{ objectFit: "cover" }} />
        </div>

        {/* ส่วนที่เลื่อนได้ */}
        <div className={styles.contentArea}>
          <div className={styles.card}>

            <div className={styles.section}>
              <h3>🧪 ตรวจสอบคุณภาพดิน</h3>
              <p>
                ตรวจความเค็มและใส่ปูน <br />
                ดินควรมีความเค็ม ≤3 dS/m <br />
                หากเกินมาตรฐาน ต้องมีมาตรการแก้ไข เช่น ปลูกพืชปรับปรุงดิน
              </p>
            </div>

            <div className={styles.section}>
              <h3>🌱 การปรับปรุงดิน</h3>
              <p>
                ใช้พืชปุ๋ยสด (ปอเทือง ถั่วพร้า ฯลฯ) <br />
                หรือวัสดุอินทรีย์ เช่น ฟางข้าว มูลสัตว์
                เพื่อเพิ่มอินทรียวัตถุ
              </p>
            </div>

            <div className={styles.section}>
              <h3>🚜 การเตรียมดิน</h3>
              <p>
                ใช้รถไถ พรวน <br />
                เพื่อพลิกดินและตัดวงจรศัตรูพืช
              </p>
            </div>

            <div className={styles.section}>
              <h3>⛰ การปรับระดับพื้นที่</h3>
              <p>
                พื้นที่ราบ ➜ ใช้เลเซอร์เลเวลลิ่ง <br />
                หรือปรับระดับให้ดินเรียบเสมอกัน <br /><br />
                พื้นที่ลาดเอียง ➜ ปลูกขวางความลาดชัน
                ใช้พืชคลุมดิน/วัสดุคลุมเพื่อลดการชะล้าง
              </p>
            </div>

          </div>
        </div>

        <BottomNav activePath="/home" />

      </div>
    </div>
  );
}
