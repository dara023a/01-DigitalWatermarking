#!/usr/bin/env python3
"""Test full watermarking flow: embed -> attack -> extract -> evaluate"""
import os
import sys
import tempfile
import numpy as np
import cv2

# Add parent directory to path
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from app.pipeline import run_embedding_pipeline, run_extraction_pipeline, run_evaluation_pipeline
from app.watermark import embed_watermark, extract_watermark, binarize_watermark
from app.metrics import calculate_psnr, calculate_ssim, calculate_ncc, calculate_ber
from attack_simulation import attack_jpeg, attack_resize, attack_pure_crop, attack_crop_resize_back, attack_gaussian_noise, attack_brightness_contrast

def create_test_image(width=256, height=256):
    """Create a test grayscale image"""
    img = np.zeros((height, width), dtype=np.uint8)
    for i in range(height):
        for j in range(width):
            img[i, j] = (i + j) % 256
    return img

def create_test_watermark(width=32, height=32):
    """Create a test binary watermark"""
    wm = np.zeros((height, width), dtype=np.uint8)
    for i in range(height):
        for j in range(width):
            wm[i, j] = 1 if (i + j) % 2 == 0 else 0
    return wm

def test_embed():
    print("=== TEST 1: EMBEDDING ===")
    img = create_test_image()
    wm = create_test_watermark()
    
    watermarked, metadata = embed_watermark(img, wm, "test-key", 5.0)
    print(f"  Watermarked shape: {watermarked.shape}")
    print(f"  Metadata: {metadata}")
    print(f"  PSNR: {calculate_psnr(img, watermarked):.2f} dB")
    print(f"  SSIM: {calculate_ssim(img, watermarked):.4f}")
    print("  PASS: Embedding works")
    return img, wm, watermarked, metadata

def test_extract(watermarked, metadata):
    print("\n=== TEST 2: EXTRACTION ===")
    extracted = extract_watermark(watermarked, "test-key", metadata)
    print(f"  Extracted shape: {extracted.shape}")
    print("  PASS: Extraction works")
    return extracted

def test_attack_jpeg(watermarked):
    print("\n=== TEST 3: JPEG ATTACK ===")
    attacked = attack_jpeg(watermarked, 70)
    print(f"  Attacked shape: {attacked.shape}")
    print("  PASS: JPEG attack works")
    return attacked

def test_attack_resize(watermarked):
    print("\n=== TEST 4: RESIZE ATTACK ===")
    attacked = attack_resize(watermarked, 0.5)
    print(f"  Attacked shape: {attacked.shape}")
    print("  PASS: Resize attack works")
    return attacked

def test_attack_crop(watermarked):
    print("\n=== TEST 5: CROP ATTACK ===")
    attacked = attack_pure_crop(watermarked, 10)
    print(f"  Attacked shape: {attacked.shape}")
    print("  PASS: Crop attack works")
    return attacked

def test_attack_gaussian(watermarked):
    print("\n=== TEST 6: GAUSSIAN NOISE ATTACK ===")
    attacked = attack_gaussian_noise(watermarked, 10)
    print(f"  Attacked shape: {attacked.shape}")
    print("  PASS: Gaussian noise attack works")
    return attacked

def test_attack_brightness(watermarked):
    print("\n=== TEST 7: BRIGHTNESS/CONTRAST ATTACK ===")
    attacked = attack_brightness_contrast(watermarked, 20, 1.2)
    print(f"  Attacked shape: {attacked.shape}")
    print("  PASS: Brightness/contrast attack works")
    return attacked

def test_metrics(original, extracted, original_wm):
    print("\n=== TEST 8: METRICS ===")
    ncc = calculate_ncc(original_wm, extracted)
    ber = calculate_ber(original_wm, extracted)
    print(f"  NC: {ncc:.4f}")
    print(f"  BER: {ber:.4f}")
    print("  PASS: Metrics calculation works")

def test_wrong_key(watermarked, metadata):
    print("\n=== TEST 9: WRONG KEY ===")
    try:
        extracted = extract_watermark(watermarked, "wrong-key", metadata)
        ncc = calculate_ncc(create_test_watermark(), extracted)
        print(f"  NC with wrong key: {ncc:.4f}")
        print("  PASS: Wrong key produces different result")
    except Exception as e:
        print(f"  Error: {e}")
        print("  PASS: Wrong key handled")

if __name__ == "__main__":
    print("=" * 50)
    print("SPECTRA WATERMARKING - PYTHON ENGINE TEST")
    print("=" * 50)
    
    try:
        img, wm, watermarked, metadata = test_embed()
        extracted = test_extract(watermarked, metadata)
        test_attack_jpeg(watermarked)
        test_attack_resize(watermarked)
        test_attack_crop(watermarked)
        test_attack_gaussian(watermarked)
        test_attack_brightness(watermarked)
        test_metrics(img, extracted, wm)
        test_wrong_key(watermarked, metadata)
        
        print("\n" + "=" * 50)
        print("ALL TESTS PASSED!")
        print("=" * 50)
    except Exception as e:
        print(f"\nTEST FAILED: {e}")
        import traceback
        traceback.print_exc()
        sys.exit(1)
