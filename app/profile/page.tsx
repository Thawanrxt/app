"use client";

import { useEffect, useState, useRef } from 'react';
import { useRouter } from 'next/navigation';
import styles from './profile.module.css';
import { Home, Sun, Map, Users, Settings, Camera } from 'lucide-react';
import BackButton from '../components/BackButton';

export default function ProfilePage() {
  const router = useRouter();
  const fileInputRef = useRef<HTMLInputElement>(null);
  
  const [user, setUser] = useState({
    displayName: 'กำลังโหลด...',
    address: '', 
    birthday: '',
    phone: '',
    profileImage: '/duck.jpg'
  });

  // 🚩 ฟังก์ชันแปลงวันที่เป็นภาษาไทย (พ.ศ.)
  const formatThaiDate = (dateString: string) => {
    if (!dateString || dateString === 'ไม่ได้ระบุวันเกิด' || dateString === 'กำลังโหลด...') return dateString;
    
    const months = [
      "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน",
      "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"
    ];

    try {
      const date = new Date(dateString);
      const day = date.getDate();
      const month = months[date.getMonth()];
      const year = date.getFullYear() + 543;
      return `${day} ${month} ${year}`;
    } catch (e) {
      return dateString;
    }
  };

  useEffect(() => {
    const userId = localStorage.getItem('user_id');
    
    if (!userId) {
      router.push('/login');
      return;
    }

    const fetchProfile = async () => {
      try {
        const response = await fetch(`http://localhost:8000/user/profile/${userId}`);
        if (response.ok) {
          const data = await response.json();
          setUser(prev => ({
            ...prev,
            displayName: data.full_name || data.username,
            address: data.address || 'ไม่ได้ระบุที่อยู่',
            birthday: data.birthday || 'ไม่ได้ระบุวันเกิด',
            phone: data.phone || 'ไม่ได้ระบุเบอร์โทรศัพท์'
          }));
        }
      } catch (error) {
        console.error("❌ Error fetching profile:", error);
      }
    };

    fetchProfile();
  }, [router]);

  const handleImageChange = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    const userId = localStorage.getItem('user_id');

    if (file && userId) {
      const formData = new FormData();
      formData.append('file', file);

      try {
        const res = await fetch(`http://localhost:8000/user/upload-profile/${userId}`, {
          method: "POST",
          body: formData
        });

        if (res.ok) {
          const data = await res.json();
          const fullUrl = `http://localhost:8000${data.image_url}`;
          setUser(prev => ({ ...prev, profileImage: fullUrl }));
          localStorage.setItem('user_profile_img', fullUrl);
          alert("อัปโหลดรูปโปรไฟล์สำเร็จ!");
        }
      } catch (err) {
        console.error("Upload error:", err);
        alert("อัปโหลดรูปไม่สำเร็จ กรุณาลองใหม่");
      }
    }
  };

  const triggerFileInput = () => {
    fileInputRef.current?.click();
  };

  return (
    <div className={styles.container}>
      {/* แถบ Banner สีเขียว */}
      <div className={styles.banner} style={{ 
        position: 'relative', 
        display: 'flex', 
        alignItems: 'center', 
        padding: '0 20px', 
        height: '150px' 
      }}>
        
        <BackButton onClick={() => router.back()} />

        {/* 2. รูปโปรไฟล์ */}
        <div className={styles.avatarWrapper} style={{ 
          position: 'relative',
          marginLeft: '20px', 
          width: '100px',
          height: '100px',
          flexShrink: 0,
          marginTop: '0' 
        }}>
          <img 
            src={user.profileImage} 
            alt="Profile" 
            style={{ width: '100%', height: '100%', borderRadius: '50%', border: '4px solid white', objectFit: 'cover' }}
          />
          <button className={styles.cameraBtn} onClick={triggerFileInput} style={{ position: 'absolute', bottom: '0', right: '0' }}>
            <Camera size={18} color="white" />
          </button>
          <input 
            type="file" 
            ref={fileInputRef} 
            onChange={handleImageChange} 
            accept="image/*" 
            style={{ display: 'none' }} 
          />
        </div>

        {/* 3. ปุ่มแก้ไขโปรไฟล์ (ปิดใช้งาน) */}
        <button 
          className={styles.editBtn} 
          disabled={true} 
          style={{ 
            marginLeft: 'auto',
            backgroundColor: '#ffffff',
            cursor: 'not-allowed',
            opacity: 0.8
          }}
        >
          แก้ไขโปรไฟล์ผู้ใช้
        </button>
      </div>

      <div className={styles.content}>
        <div className={styles.usernameDisplay}>
          <h1>{user.displayName}</h1>
          <span className={styles.roleBadge}>Smart Farmer</span>
        </div>

        <div className={styles.infoCard}>
          <div className={styles.infoItem}>
            <div className={styles.label}>
              <span className={styles.labelText}>ชื่อที่แสดง</span>
              <span className={styles.valueText}>{user.displayName}</span>
            </div>
          </div>

          <div className={styles.infoItem}>
            <div className={styles.label}>
              <span className={styles.labelText}>ที่อยู่</span>
              <span className={styles.valueText}>{user.address}</span> 
            </div>
          </div>

          <div className={styles.infoItem}>
            <div className={styles.label}>
              <span className={styles.labelText}>วันเกิด</span>
              {/* 🚩 แสดงวันเกิดแบบไทย */}
              <span className={styles.valueText}>{formatThaiDate(user.birthday)}</span>
            </div>
          </div>

          <div className={styles.infoItem}>
            <div className={styles.label}>
              <span className={styles.labelText}>เบอร์โทรศัพท์</span>
              <span className={styles.valueText}>{user.phone}</span>
            </div>
          </div>
        </div>
      </div>

      <nav className={styles.bottomNav}>
        <div className={styles.navItem} onClick={() => router.push('/home')}>
          <Home size={20} />
          <span>หน้าหลัก</span>
        </div>
        <div className={styles.navItem} onClick={() => router.push('/weather')}>
          <Sun size={20} />
          <span>อากาศ</span>
        </div>
        <div className={styles.navItem} onClick={() => router.push('/map')}>
          <Map size={20} />
          <span>แผนที่</span>
        </div>
        <div className={styles.navItem} onClick={() => router.push('/reports')}>
          <Users size={20} />
          <span>รายงาน</span>
        </div>
        <div className={`${styles.navItem} ${styles.active}`} onClick={() => router.push('/profile')}>
          <Settings size={20} />
          <span>ตั้งค่า</span>
        </div>
      </nav>
    </div>
  );
}