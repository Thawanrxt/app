"use client";

import Image from "next/image";
import { useRouter } from "next/navigation";
import styles from "./fertilizer.module.css";
import BackButton from "../../components/BackButton";
import BottomNav from "../../components/BottomNav";

const guidelineItems = [
  "วิเคราะห์ดินก่อนปลูก: เพื่อทราบความต้องการธาตุอาหารของพื้นที่",
  "เลือกใช้ปุ๋ยอย่างเหมาะสม: ทั้งชนิดและปริมาณตามผลวิเคราะห์ดิน",
  "ใช้ปุ๋ยอินทรีย์ร่วมกับปุ๋ยเคมี: เพื่อเพิ่มความอุดมสมบูรณ์ของดินในระยะยาว",
  "แบ่งใส่ปุ๋ยตามช่วงการเจริญเติบโตของข้าว: เช่น ระยะต้นกล้า ระยะตั้งท้อง และระยะข้าวเริ่มออกรวง",
  "หลีกเลี่ยงการใส่ปุ๋ยในช่วงฝนตกหนัก: เพื่อป้องกันการชะล้างและสูญเสียธาตุอาหาร",
];

export default function FertilizerPage() {
  const router = useRouter();

  return (
    <div className={styles.page}>
      <header className={styles.header}>
        <BackButton onClick={() => router.push("/home")} className={styles.backBtn} />
        <h1>การจัดการธาตุอาหาร</h1>
      </header>

      <main className={styles.content}>
        <div className={styles.imageWrap}>
          <Image src="/fertilizer.jpg" alt="การจัดการธาตุอาหาร" fill priority className={styles.image} />
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
