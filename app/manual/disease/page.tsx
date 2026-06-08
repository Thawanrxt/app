"use client";

import Image from "next/image";
import { useRouter } from "next/navigation";
import styles from "./disease.module.css";
import BackButton from "../../components/BackButton";
import BottomNav from "../../components/BottomNav";

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

export default function DiseasePage() {
  const router = useRouter();

  return (
    <div className={styles.page}>
      <header className={styles.header}>
        <BackButton onClick={() => router.push("/home")} className={styles.backBtn} />
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

      <BottomNav activePath="/home" />
    </div>
  );
}
