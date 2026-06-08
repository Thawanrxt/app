"use client";

import Image from "next/image";
import { useRouter } from "next/navigation";
import styles from "./harvest.module.css";
import BackButton from "../../components/BackButton";
import BottomNav from "../../components/BottomNav";

export default function HarvestPage() {
  const router = useRouter();

  return (
    <div className={styles.page}>
      <header className={styles.header}>
        <BackButton onClick={() => router.push("/home")} className={styles.backBtn} />
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

      <BottomNav activePath="/home" />
    </div>
  );
}
