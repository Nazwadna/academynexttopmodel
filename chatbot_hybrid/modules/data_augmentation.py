# =========================
# DATA AUGMENTATION MODULE
# =========================

import random
import re
import json
import numpy as np
from typing import List, Dict, Any
import tensorflow as tf
from tensorflow.keras.preprocessing.text import Tokenizer
from tensorflow.keras.preprocessing.sequence import pad_sequences

# Indonesian synonyms dictionary
INDONESIAN_SYNONYMS = {
    "lokasi": ["tempat", "gedung", "alamat", "posisi"],
    "dimana": ["di mana", "berada", "terletak"],
    "biaya": ["harga", "tarif", "bayaran", "pembayaran"],
    "program": ["kursus", "pelatihan", "pendidikan", "belajar"],
    "model": ["peserta", "siswa", "murid", "talent"],
    "daftar": ["registrasi", "pendaftaran", "mendaftar"],
    "info": ["informasi", "keterangan", "penjelasan"],
    "jadwal": ["waktu", "jam", "sesi", "tanggal"],
    "contact": ["hubungi", "kontak", "telepon", "whatsapp"],
    "academy": ["lembaga", "sekolah", "tempat belajar"],
    "top": ["terbaik", "utama", "primer", "nomor satu"],
    "next": ["berikut", "selanjutnya", "mendatang"],
    "training": ["pelatihan", "kursus", "belajar", "edukasi"],
    "join": ["bergabung", "ikut", "partisipasi"],
    "how": ["bagaimana", "cara", "langkah"],
    "what": ["apa", "sesuatu", "hal"],
    "when": ["kapan", "waktu", "tanggal"],
    "why": ["mengapa", "kenapa", "alasan"],
    "can": ["bisa", "dapat", "mampu"],
    "will": ["akan", "bakal", "mau"],
    "want": ["ingin", "mau", "kehendaki"],
    "need": ["butuh", "perlu", "memerlukan"],
    "help": ["bantuan", "tolong", "bantu"],
    "ask": ["tanya", "bertanya", "menanyakan"],
    "get": ["dapatkan", "peroleh", "ambil"],
    "make": ["buat", "cetak", "hasilkan"],
    "find": ["cari", "temukan", "lihat"],
    "look": ["lihat", "perhatikan", "amati"],
    "see": ["lihat", "saksikan", "tonton"],
    "good": ["bagus", "baik", "hebat", "mantap"],
    "bad": ["buruk", "jelek", "tidak bagus"],
    "big": ["besar", "luas", "gede"],
    "small": ["kecil", "sedikit", "mungil"],
    "fast": ["cepat", "kilat", "gesit"],
    "slow": ["lambat", "pelan", "lama"],
    "easy": ["mudah", "gampang", "simpel"],
    "hard": ["sulit", "susah", "rumit"],
    "new": ["baru", "terbaru", "fresh"],
    "old": ["lama", "tua", "jadul"],
    "first": ["pertama", "awal", "mula-mula"],
    "last": ["terakhir", "akhir", "pucuk"],
    "many": ["banyak", "berbagai", "macam-macam"],
    "few": ["sedikit", "beberapa", "sedikit"],
    "all": ["semua", "seluruh", "segala"],
    "some": ["beberapa", "sebagian", "ada"],
    "no": ["tidak", "bukan", "jangan"],
    "yes": ["ya", "benar", "betul", "oke"],
    "maybe": ["mungkin", "kayaknya", "sepertinya"],
    "always": ["selalu", "terus", "senantiasa"],
    "never": ["tidak pernah", "belum pernah", "tak pernah"],
    "sometimes": ["kadang", "terkadang", "sekali-sekali"],
    "usually": ["biasanya", "umumnya", "lazimnya"],
    "really": ["sungguh", "benar-benar", "betul-betul"],
    "very": ["sangat", "amat", "sekali"],
    "quite": ["cukup", "agak", "lumayan"],
    "too": ["terlalu", "amat", "sangat"],
    "more": ["lebih", "lagi", "tambahan"],
    "less": ["kurang", "sedikit", "rendah"],
    "most": ["paling", "ter-", "paling"],
    "least": ["paling sedikit", "paling kurang", "paling rendah"],
    "only": ["hanya", "cuma", "sekadar"],
    "also": ["juga", "pun", "pula"],
    "because": ["karena", "sebab", "disebabkan"],
    "so": ["jadi", "maka", "oleh karena"],
    "but": ["tapi", "namun", "melainkan"],
    "if": ["jika", "kalau", "apabila"],
    "than": ["daripada", "dari", "bandingkan"],
    "or": ["atau", "maupun", "ataupun"],
    "and": ["dan", "serta", "lalu"],
    "with": ["dengan", "bersama", "beserta"],
    "without": ["tanpa", "tidak dengan", "minus"],
    "for": ["untuk", "bagi", "guna"],
    "from": ["dari", "daripada", "sejak"],
    "to": ["ke", "menuju", "sampai"],
    "at": ["di", "pada", "saat"],
    "in": ["di", "dalam", "pada"],
    "on": ["di", "atas", "tentang"],
    "by": ["oleh", "melalui", "dengan"],
    "about": ["tentang", "mengenai", "perihal"],
    "like": ["seperti", "ibarat", "laksana"],
    "as": ["seperti", "sebagai", "bagai"],
    "than": ["daripada", "dari", "bandingkan"],
    "up": ["naik", "atas", "ke atas"],
    "down": ["turun", "bawah", "ke bawah"],
    "out": ["keluar", "luar", "ke luar"],
    "off": ["mati", "tidak aktif", "padam"],
    "over": ["selesai", "habis", "rampung"],
    "through": ["melalui", "lewat", "melewati"],
    "between": ["antara", "di antara", "di tengah"],
    "among": ["di antara", "di antar", "antara"],
    "during": ["selama", "ketika", "waktu"],
    "before": ["sebelum", "dahulu", "sejak"],
    "after": ["setelah", "sesudah", "kemudian"],
    "again": ["lagi", "kembali", "pulang"],
    "once": ["sekali", "suatu kali", "pernah"],
    "twice": ["dua kali", "berkali-kali", "berulang-ulang"],
    "three": ["tiga", "tiga kali", "berulang tiga kali"],
    "time": ["kali", "waktu", "saat"],
    "times": ["kali", "waktu", "saat"],
    "way": ["cara", "jalan", "metode"],
    "ways": ["cara", "jalan", "metode"],
    "thing": ["hal", "sesuatu", "benda"],
    "things": ["hal-hal", "sesuatu", "benda-benda"],
    "something": ["sesuatu", "hal", "benda"],
    "anything": ["apa saja", "segala sesuatu", "apa pun"],
    "nothing": ["tidak ada", "kosong", "nihil"],
    "everything": ["segala sesuatu", "semua hal", "segalanya"],
    "everyone": ["semua orang", "semua", "segala orang"],
    "someone": ["seseorang", "seseorang", "ada orang"],
    "anyone": ["siapa saja", "siapa pun", "barang siapa"],
    "no one": ["tidak ada", "tidak seorang pun", "nihil"],
    "every": ["setiap", "tiap", "masing-masing"],
    "each": ["masing-masing", "tiap", "setiap"],
    "both": ["keduanya", "kedua-duanya", "kedua-dua"],
    "either": ["baik...atau", "entah...atau", "baik...maupun"],
    "neither": ["tidak...pun", "tidak...maupun", "bukan...maupun"],
    "not": ["tidak", "bukan", "jangan"],
    "never": ["tidak pernah", "belum pernah", "tak pernah"],
    "always": ["selalu", "terus", "senantiasa"],
    "sometimes": ["kadang", "terkadang", "sekali-sekali"],
    "usually": ["biasanya", "umumnya", "lazimnya"],
    "often": ["sering", "kerap", "acap kali"],
    "rarely": ["jarang", "sekali-sekali", "sangka-sangka"],
    "seldom": ["jarang", "sekali-sekali", "sangka-sangka"],
    "always": ["selalu", "terus", "senantiasa"],
    "never": ["tidak pernah", "belum pernah", "tak pernah"],
    "sometimes": ["kadang", "terkadang", "sekali-sekali"],
    "usually": ["biasanya", "umumnya", "lazimnya"],
    "often": ["sering", "kerap", "acap kali"],
    "rarely": ["jarang", "sekali-sekali", "sangka-sangka"],
    "seldom": ["jarang", "sekali-sekali", "sangka-sangka"]
}

class EasyDataAugmented:
    """
    Easy Data Augmentation class for Indonesian text
    Implements SR, RI, RS, RD techniques
    """
    
    def __init__(self, augmentation_prob: float = 0.3):
        self.augmentation_prob = augmentation_prob
        self.synonyms = INDONESIAN_SYNONYMS
        
    def synonym_replacement(self, text: str) -> str:
        """
        Synonym Replacement (SR)
        Replace words with their Indonesian synonyms
        """
        words = text.split()
        augmented_words = []
        
        for word in words:
            clean_word = re.sub(r'[^\w]', '', word.lower())
            if clean_word in self.synonyms and random.random() < self.augmentation_prob:
                synonyms = self.synonyms[clean_word]
                if synonyms:
                    replacement = random.choice(synonyms)
                    # Preserve original case
                    if word[0].isupper():
                        replacement = replacement.capitalize()
                    augmented_words.append(replacement)
                else:
                    augmented_words.append(word)
            else:
                augmented_words.append(word)
        
        return ' '.join(augmented_words)
    
    def random_insertion(self, text: str) -> str:
        """
        Random Insertion (RI)
        Insert random Indonesian words
        """
        # Common Indonesian words for insertion
        indonesian_words = [
            "yang", "dan", "di", "ke", "untuk", "dari", "pada", "dengan",
            "adalah", "ini", "itu", "saja", "juga", "bisa", "akan",
            "tentang", "seperti", "jika", "karena", "tapi", "atau",
            "bagi", "oleh", "sebagai", "tersebut", "lebih", "sudah",
            "masih", "belum", "pernah", "selalu", "hanya", "sangat",
            "cukup", "baik", "buruk", "besar", "kecil", "lama",
            "baru", "tua", "tinggi", "rendah", "cepat", "lambat"
        ]
        
        words = text.split()
        if len(words) < 2:
            return text
            
        # Random insertion
        num_insertions = max(1, len(words) // 5)  # Insert 20% of text length
        
        for _ in range(num_insertions):
            if random.random() < self.augmentation_prob:
                insert_pos = random.randint(0, len(words))
                insert_word = random.choice(indonesian_words)
                words.insert(insert_pos, insert_word)
        
        return ' '.join(words)
    
    def random_swap(self, text: str) -> str:
        """
        Random Swap (RS)
        Randomly swap adjacent words
        """
        words = text.split()
        if len(words) < 2:
            return text
            
        # Number of swaps
        num_swaps = max(1, len(words) // 10)  # Swap 10% of word pairs
        
        for _ in range(num_swaps):
            if random.random() < self.augmentation_prob:
                i = random.randint(0, len(words) - 2)
                words[i], words[i + 1] = words[i + 1], words[i]
        
        return ' '.join(words)
    
    def random_deletion(self, text: str) -> str:
        """
        Random Deletion (RD)
        Randomly remove words
        """
        words = text.split()
        if len(words) < 3:
            return text
            
        # Number of deletions
        num_deletions = max(1, len(words) // 15)  # Delete ~7% of words
        
        for _ in range(num_deletions):
            if random.random() < self.augmentation_prob and len(words) > 2:
                delete_pos = random.randint(0, len(words) - 1)
                words.pop(delete_pos)
        
        return ' '.join(words)
    
    def augment_text(self, text: str, techniques: List[str] = None) -> List[str]:
        """
        Apply multiple augmentation techniques
        """
        if techniques is None:
            techniques = ['sr', 'ri', 'rs', 'rd']
        
        augmented_texts = [text]  # Original text
        
        # Apply Synonym Replacement
        if 'sr' in techniques:
            sr_text = self.synonym_replacement(text)
            augmented_texts.append(sr_text)
        
        # Apply Random Insertion
        if 'ri' in techniques:
            ri_text = self.random_insertion(text)
            augmented_texts.append(ri_text)
        
        # Apply Random Swap
        if 'rs' in techniques:
            rs_text = self.random_swap(text)
            augmented_texts.append(rs_text)
        
        # Apply Random Deletion
        if 'rd' in techniques:
            rd_text = self.random_deletion(text)
            augmented_texts.append(rd_text)
        
        # Apply combinations
        if 'sr_ri' in techniques:
            sr_ri_text = self.synonym_replacement(text)
            sr_ri_text = self.random_insertion(sr_ri_text)
            augmented_texts.append(sr_ri_text)
        
        if 'sr_rs' in techniques:
            sr_rs_text = self.synonym_replacement(text)
            sr_rs_text = self.random_swap(sr_rs_text)
            augmented_texts.append(sr_rs_text)
        
        if 'ri_rs' in techniques:
            ri_rs_text = self.random_insertion(text)
            ri_rs_text = self.random_swap(ri_rs_text)
            augmented_texts.append(ri_rs_text)
        
        # Apply all techniques
        if 'all' in techniques:
            all_text = self.synonym_replacement(text)
            all_text = self.random_insertion(all_text)
            all_text = self.random_swap(all_text)
            all_text = self.random_deletion(all_text)
            augmented_texts.append(all_text)
        
        return augmented_texts

class TensorFlowDataProcessor:
    """
    TensorFlow-based data processing for chatbot
    """
    
    def __init__(self, max_words: int = 1000, max_len: int = 100):
        self.max_words = max_words
        self.max_len = max_len
        self.tokenizer = Tokenizer(num_words=max_words, oov_token="<OOV>")
        
    def preprocess_texts(self, texts: List[str]) -> np.ndarray:
        """
        Preprocess texts using TensorFlow
        """
        # Fit tokenizer on texts
        self.tokenizer.fit_on_texts(texts)
        
        # Convert texts to sequences
        sequences = self.tokenizer.texts_to_sequences(texts)
        
        # Pad sequences
        padded_sequences = pad_sequences(sequences, maxlen=self.max_len, padding='post', truncating='post')
        
        return np.array(padded_sequences)
    
    def create_training_data(self, texts: List[str], labels: List[str]) -> tuple:
        """
        Create training data for TensorFlow model
        """
        # Preprocess texts
        X = self.preprocess_texts(texts)
        
        # Convert labels to categorical
        label_to_int = {label: i for i, label in enumerate(set(labels))}
        y = np.array([label_to_int[label] for label in labels])
        
        return X, y, label_to_int

# Global instances
data_augmenter = EasyDataAugmented(augmentation_prob=0.3)
tf_processor = TensorFlowDataProcessor()

def augment_training_data(training_examples: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
    """
    Augment training data for better model performance
    """
    augmented_data = []
    
    for example in training_examples:
        original_text = example.get('text', '')
        intent = example.get('intent', '')
        
        # Generate augmented versions
        augmented_texts = data_augmenter.augment_text(original_text, ['sr', 'ri', 'rs', 'rd'])
        
        for aug_text in augmented_texts:
            augmented_data.append({
                'text': aug_text,
                'intent': intent,
                'is_augmented': aug_text != original_text
            })
    
    return augmented_data

def create_tensorflow_dataset(texts: List[str], labels: List[str]) -> tuple:
    """
    Create TensorFlow dataset from texts and labels
    """
    return tf_processor.create_training_data(texts, labels)

def test_augmentation():
    """
    Test data augmentation techniques
    """
    test_text = "lokasi academy next top model dimana"
    
    print("Original text:", test_text)
    print("\n--- Augmentation Results ---")
    
    # Test individual techniques
    sr_result = data_augmenter.synonym_replacement(test_text)
    print("Synonym Replacement (SR):", sr_result)
    
    ri_result = data_augmenter.random_insertion(test_text)
    print("Random Insertion (RI):", ri_result)
    
    rs_result = data_augmenter.random_swap(test_text)
    print("Random Swap (RS):", rs_result)
    
    rd_result = data_augmenter.random_deletion(test_text)
    print("Random Deletion (RD):", rd_result)
    
    # Test combinations
    all_results = data_augmenter.augment_text(test_text, ['all'])
    print("\nAll Techniques Combined:", all_results[-1])

if __name__ == "__main__":
    test_augmentation()
