"use client";

import Image from "next/image";
import { useRouter } from "next/navigation";
import styles from "./bug.module.css";
import BackButton from "../../components/BackButton";
import BottomNav from "../../components/BottomNav";

const guidelineItems = [
  "การเฝ้าระวังศัตรูพืชอย่างสม่ำเสมอ ตรวจสอบแปลงนาเพื่อประเมินระดับการระบาดก่อนตัดสินใจใช้วิธีควบคุม",
  "การใช้วิธีป้องกันก่อนเกิดปัญหา เช่น การปลูกพืชหมุนเวียน การเลือกพันธุ์ต้านทาน การจัดการน้ำและดินให้เหมาะสม",
  "การใช้วิธีชีวภาพและวิธีทางกล เช่น การปล่อยแมลงศัตรูธรรมชาติ การใช้กับดัก การกำจัดด้วยมือ",
  "การใช้สารเคมีอย่างมีเหตุผล ใช้เฉพาะเมื่อจำเป็นและเลือกสารที่มีความปลอดภัยสูงต่อผู้ใช้และสิ่งแวดล้อม",
  "การบันทึกข้อมูลการจัดการศัตรูพืช เพื่อใช้ในการวิเคราะห์และปรับปรุงแนวทางในฤดูกาลถัดไป",
];

export default function BugPage() {
  const router = useRouter();

  return (
    <div className={styles.page}>
      <header className={styles.header}>
        <BackButton onClick={() => router.push("/home")} className={styles.backBtn} />
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

      <BottomNav activePath="/home" />
    </div>
  );
}
