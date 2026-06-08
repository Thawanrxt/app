"use client";

import Image from "next/image";
import { useRouter } from "next/navigation";
import styles from "./water.module.css";
import BackButton from "../../components/BackButton";
import BottomNav from "../../components/BottomNav";

const guidelineItems = [
  "วางแผนการใช้น้ำล่วงหน้า เช่น การกำหนดช่วงเวลาที่ต้องการน้ำมากที่สุด และการจัดสรรน้ำตามฤดูกาล",
  "ใช้เทคนิคการปลูกแบบเปียกสลับแห้ง (AWD) เพื่อประหยัดน้ำและลดการปล่อยก๊าซมีเทนจากนาข้าว",
  "ตรวจสอบระดับน้ำในแปลงนาอย่างสม่ำเสมอ เพื่อปรับการให้น้ำให้เหมาะสมกับระยะการเจริญเติบโตของข้าว",
  "หลีกเลี่ยงการปล่อยน้ำทิ้งโดยไม่ผ่านการกรองหรือบำบัด เพื่อป้องกันการปนเปื้อนของสารเคมีหรือตะกอนสู่แหล่งน้ำธรรมชาติ",
  "บันทึกการใช้น้ำในแต่ละรอบการผลิต เพื่อใช้ในการวิเคราะห์และปรับปรุงการจัดการในอนาคต",
];

export default function WaterPage() {
  const router = useRouter();

  return (
    <div className={styles.page}>
      <header className={styles.header}>
        <BackButton onClick={() => router.push("/home")} className={styles.backBtn} />
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

      <BottomNav activePath="/home" />
    </div>
  );
}
