package api

import (
	"fmt"
	"math"
	"strings"
)

type orderInput struct {
	Customer string `json:"customer"`
	Items    []struct {
		SKU string `json:"sku"`
		Qty int64  `json:"qty"`
	} `json:"items"`
}

func validateOrder(in orderInput) error {
	if strings.TrimSpace(in.Customer) == "" || len(in.Customer) > 200 {
		return fmt.Errorf("Nama pelanggan wajib diisi, maksimal 200 karakter")
	}
	if len(in.Items) == 0 || len(in.Items) > 100 {
		return fmt.Errorf("Pesanan harus memiliki 1–100 item")
	}
	seen := map[string]bool{}
	for _, i := range in.Items {
		if i.SKU == "" || i.SKU != strings.TrimSpace(i.SKU) || i.Qty <= 0 || i.Qty > math.MaxInt32 {
			return fmt.Errorf("SKU wajib diisi dan qty harus integer 1–2147483647")
		}
		if seen[i.SKU] {
			return fmt.Errorf("SKU %s muncul lebih dari satu kali", i.SKU)
		}
		seen[i.SKU] = true
	}
	return nil
}
